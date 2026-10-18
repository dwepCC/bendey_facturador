<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

use App\Entity\Empresa;
use App\Entity\FiscalAuditLog;
use App\Entity\FiscalDocument;
use App\Repository\EmpresaRepository;
use App\Service\Fiscal\CdrNormalizer;
use App\Service\Fiscal\FiscalQueueService;
use App\Service\Fiscal\FiscalStorageService;
use App\Service\Fiscal\FiscalWebhookService;
use App\Service\Fiscal\Observability\FiscalAuditService;
use App\Service\Fiscal\Provider\FiscalTransientErrorClassifier;
use App\Service\Fiscal\Provider\PseResponseFormatter;
use App\Service\Fiscal\Provider\SunatCdrClassifier;
use Doctrine\ORM\EntityManagerInterface;
use Greenter\Model\Response\StatusCdrResult;
use Greenter\Ws\Reader\DomCdrReader;
use Greenter\Ws\Reader\XmlReader;
use Psr\Log\LoggerInterface;

/**
 * «Verificar en SUNAT» del panel del facturador.
 *
 * verify() solo CONSULTA y describe lo que respondió SUNAT: no escribe nada. apply() lo aplica,
 * y solo lo llama el panel cuando el operador confirma. El tenant (ERP/Resto) no tiene esta acción:
 * su base se entera del nuevo estado por el webhook de siempre.
 *
 * Caso que lo motiva (2026-10-18): 16 comprobantes «rechazados» con 1033 ("ya registrado"); al
 * consultarlos, los 3 de SUNAT directo resultaron aceptados en SUNAT.
 */
class FiscalSunatVerifier
{
    /** Desde estos estados el operador puede aplicar el resultado de la consulta. */
    public const APPLICABLE_FROM = [
        FiscalDocument::STATUS_REJECTED,
        FiscalDocument::STATUS_ERROR,
        FiscalDocument::STATUS_RETRYING,
    ];

    public function __construct(
        private readonly EmpresaRepository $empresaRepo,
        private readonly SunatConsultClientInterface $sunat,
        private readonly ValidaPseConsultClientInterface $validaPse,
        private readonly EntityManagerInterface $em,
        private readonly FiscalStorageService $storage,
        private readonly FiscalWebhookService $webhook,
        private readonly FiscalQueueService $queue,
        private readonly LoggerInterface $logger,
        private readonly ?FiscalAuditService $audit = null,
        private readonly ?SunatValidezApiClientInterface $validez = null,
    ) {
    }

    public function verify(FiscalDocument $doc): VerificationResult
    {
        $ruc = $this->rucOf($doc);
        $empresa = $ruc !== '' ? $this->empresaRepo->find($ruc) : null;
        if (!$empresa instanceof Empresa) {
            return VerificationResult::unavailable('La empresa emisora no está registrada en el facturador.');
        }
        if (strtolower(trim($empresa->getAmbiente())) !== 'produccion') {
            return VerificationResult::unavailable('SUNAT solo permite consultar comprobantes de producción; esta empresa está en pruebas.');
        }

        // Orden (decisión del dueño, 2026-10-18):
        //  - PSE: 1) ValidaPSE /api/cpe/consultar; si falla o no responde, consulta directa a SUNAT:
        //    2) usuario SOL secundario del tenant → billConsultService (estado + CDR);
        //    3) API de validez del tenant, si está configurada → existe/válido, sin CDR.
        //  - SUNAT directo: 2) y luego 3).
        // Las credenciales GRE (guías) son otras y no se usan para consultar.
        $tried = [];
        $steps = [];
        if (strtolower(trim($empresa->getSendMode())) === 'pse') {
            $steps[] = fn (): VerificationResult => $this->verifyWithValidaPse($doc, $empresa, $ruc);
        }
        if (self::hasUsableSolCredentials($empresa)) {
            $steps[] = fn (): VerificationResult => $this->verifyWithSunat($doc, $empresa, $ruc);
        } else {
            $tried[] = 'sin usuario SOL secundario';
        }
        if ($this->validez !== null && $empresa->isCpeApiConfigured()) {
            $steps[] = fn (): VerificationResult => $this->verifyWithValidezApi($doc, $empresa, $ruc);
        }

        $last = null;
        foreach ($steps as $step) {
            try {
                $result = $step();
            } catch (\Throwable $e) {
                $this->logger->warning('fiscal_verify_failed', ['uuid' => $doc->getDocumentUuid(), 'error' => $e->getMessage()]);
                $result = VerificationResult::unavailable('La consulta falló: ' . $e->getMessage());
            }
            if ($result->outcome !== VerificationResult::UNAVAILABLE) {
                return $result;
            }
            $tried[] = ($result->source ?? 'consulta') . ': ' . ($result->reason ?? 'sin respuesta');
            $last = $result;
        }

        if ($last === null) {
            return VerificationResult::unavailable(
                'La empresa no tiene usuario SOL secundario ni credenciales de la API de validez de SUNAT. Regístrelos en el Panel Central (Configuración fiscal).'
            );
        }
        if (count($tried) > 1) {
            $last->reason = implode(' · ', $tried);
        }

        return $last;
    }

    private function verifyWithValidezApi(FiscalDocument $doc, Empresa $empresa, string $ruc): VerificationResult
    {
        $snapshot = json_decode($doc->getSnapshotJson(), true);
        $data = is_array($snapshot) ? ($snapshot['document'] ?? $snapshot) : [];
        $fecha = self::fechaDdMmAaaa((string) ($data['fechaEmision'] ?? ''));
        if ($fecha === null) {
            return VerificationResult::unavailable('El comprobante no tiene fecha de emisión para la API de validez.', VerificationResult::SOURCE_SUNAT_VALIDEZ);
        }
        $consulta = [
            'numRuc' => $ruc,
            'codComp' => $doc->getDocumentType(),
            'numeroSerie' => $doc->getSeries(),
            'numero' => (int) $doc->getNumber(),
            'fechaEmision' => $fecha,
        ];
        if (isset($data['mtoImpVenta']) && is_numeric($data['mtoImpVenta'])) {
            $consulta['monto'] = round((float) $data['mtoImpVenta'], 2);
        }
        $resp = $this->validez->validar((string) $empresa->getCpeApiClientId(), (string) $empresa->getCpeApiClientSecret(), $ruc, $consulta);

        return self::fromValidezApiResponse($resp['http'], $resp['body']);
    }

    /**
     * estadoCp (manual SUNAT): 0 NO EXISTE · 1 ACEPTADO · 2 ANULADO · 4 NO AUTORIZADO. La API
     * compara fecha y monto: si no coinciden, el comprobante de SUNAT no es el nuestro.
     */
    public static function fromValidezApiResponse(int $http, string $body): VerificationResult
    {
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return VerificationResult::unavailable('La API de validez no respondió JSON (HTTP ' . $http . ').', VerificationResult::SOURCE_SUNAT_VALIDEZ);
        }
        $message = trim((string) ($json['message'] ?? ''));
        if (($json['success'] ?? false) !== true || !is_array($json['data'] ?? null)) {
            return VerificationResult::unavailable(
                'La API de validez no pudo responder' . ($message !== '' ? ': ' . $message : '') . ' (HTTP ' . $http . ').',
                VerificationResult::SOURCE_SUNAT_VALIDEZ,
                isset($json['errorCode']) ? (string) $json['errorCode'] : null,
                $message !== '' ? $message : null
            );
        }
        $data = $json['data'];
        $estado = isset($data['estadoCp']) ? (string) $data['estadoCp'] : '';
        $obs = $data['observaciones'] ?? $data['Observaciones'] ?? [];
        $obs = is_array($obs) ? array_values(array_filter(array_map('strval', $obs))) : [];

        $out = new VerificationResult();
        $out->source = VerificationResult::SOURCE_SUNAT_VALIDEZ;
        $out->statusCode = 'estadoCp=' . $estado;
        $out->statusMessage = $message !== '' ? $message : null;
        $out->cdrNotes = $obs;
        $out->outcome = match ($estado) {
            '1' => VerificationResult::ACCEPTED,
            '2' => VerificationResult::VOIDED,
            '0' => VerificationResult::NOT_FOUND,
            default => VerificationResult::UNAVAILABLE,
        };
        if ($out->outcome === VerificationResult::ACCEPTED) {
            $out->reason = 'SUNAT confirma el comprobante como ACEPTADO (fecha y monto coinciden) pero esta API no entrega el CDR.';
        } elseif ($estado === '4') {
            $out->reason = 'SUNAT responde NO AUTORIZADO (estadoCp=4).';
        } elseif ($out->outcome === VerificationResult::UNAVAILABLE) {
            $out->reason = 'Respuesta de la API de validez sin conclusión (estadoCp=' . $estado . ').';
        }
        if ($obs !== []) {
            $out->reason = trim(($out->reason ?? '') . ' Observaciones: ' . implode(' | ', $obs));
        }

        return $out;
    }

    private static function fechaDdMmAaaa(string $iso): ?string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', trim($iso), $m) !== 1) {
            return null;
        }

        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    /**
     * Aplica el resultado de una NUEVA consulta (no se confía en lo que manda el navegador). Si
     * SUNAT responde algo distinto de lo que vio el operador, no se aplica nada.
     *
     * @throws VerificationApplyException
     */
    public function apply(FiscalDocument $doc, string $expectedOutcome, string $operator): VerificationResult
    {
        if (!in_array($doc->getStatus(), self::APPLICABLE_FROM, true)) {
            throw new VerificationApplyException(sprintf(
                'El comprobante está «%s»: solo se aplica la verificación sobre rechazados, con error o reintentando.',
                $doc->getStatus()
            ));
        }

        $result = $this->verify($doc);
        if ($result->outcome !== $expectedOutcome) {
            throw new VerificationApplyException(sprintf(
                'SUNAT respondió distinto al volver a consultar (ahora: %s). Revise el resultado antes de aplicar.',
                $result->label()
            ), $result);
        }
        if (!$result->isApplicable()) {
            throw new VerificationApplyException('Este resultado no cambia el estado del comprobante: ' . $result->label(), $result);
        }

        $previous = [
            'status' => $doc->getStatus(),
            'sunat_code' => $doc->getSunatCode(),
            'sunat_message' => $doc->getSunatMessage(),
        ];

        if ($result->cdrZip !== null && $result->cdrZip !== '') {
            $doc->setCdrUrl($this->storage->storeCdr(
                $doc->getTenantSlug(),
                $doc->getDocumentType(),
                $doc->getSeries(),
                $doc->getNumber(),
                $result->cdrZip
            ));
        }

        $now = new \DateTimeImmutable();
        $status = (string) $result->proposedStatus();
        $doc->setStatus($status);
        $doc->setSunatCode($result->cdrCode ?? ($status === FiscalDocument::STATUS_REJECTED ? $result->statusCode : '0'));
        $doc->setSunatMessage(self::messageFor($result));
        $doc->setNextRetryAt(null);
        if ($status === FiscalDocument::STATUS_REJECTED) {
            $doc->setRejectedAt($doc->getRejectedAt() ?? $now);
        } else {
            $doc->setAcceptedAt($doc->getAcceptedAt() ?? $now);
            $doc->setRejectedAt(null);
        }
        $doc->setVerifiedAt($now);
        $doc->setVerifiedBy($operator);
        $doc->setVerificationSource($result->source);
        $doc->setVerificationJson(json_encode(
            $result->toArray() + ['applied_at' => $now->format(DATE_ATOM), 'applied_by' => $operator, 'previous' => $previous],
            JSON_UNESCAPED_UNICODE
        ) ?: null);
        $this->em->flush();

        $this->logger->info('fiscal_verify_applied', [
            'uuid' => $doc->getDocumentUuid(),
            'from' => $previous['status'],
            'to' => $status,
            'source' => $result->source,
            'operator' => $operator,
        ]);
        try {
            $this->audit?->fromDocument($doc, 'fiscal_manual_verification_applied', FiscalAuditLog::STATUS_SUCCESS, [
                'error_code' => $doc->getSunatCode(),
                'error_message' => $doc->getSunatMessage(),
                'metadata_json' => json_encode(['operator' => $operator, 'source' => $result->source, 'previous' => $previous], JSON_UNESCAPED_UNICODE) ?: null,
            ]);
        } catch (\Throwable) {
        }

        // La base del tenant se actualiza por el webhook de siempre.
        try {
            $this->webhook->notifyStatus($doc);
        } catch (\Throwable) {
            $this->queue->push(FiscalQueueService::QUEUE_WEBHOOK_SYNC, ['document_uuid' => $doc->getDocumentUuid(), 'attempt' => 1]);
        }

        return $result;
    }

    /**
     * Las empresas PSE guardan «PSE»/«PSE» de relleno en la clave SOL: con eso SUNAT responde
     * HTTP Unauthorized. Un usuario SOL real es RUC (11 dígitos) + usuario.
     */
    public static function hasUsableSolCredentials(Empresa $empresa): bool
    {
        $user = trim($empresa->getSolUser());
        $pass = trim($empresa->getSolPass());

        return preg_match('/^\d{11}\S+$/', $user) === 1 && $pass !== '' && strtoupper($pass) !== 'PSE';
    }

    private function verifyWithSunat(FiscalDocument $doc, Empresa $empresa, string $ruc): VerificationResult
    {
        $user = trim($empresa->getSolUser());
        $pass = trim($empresa->getSolPass());
        $tipo = $doc->getDocumentType();
        $serie = $doc->getSeries();
        $numero = (int) $doc->getNumber();

        $cdr = $this->sunat->getStatusCdr($user, $pass, $ruc, $tipo, $serie, $numero);
        $fromCdr = self::fromSunatCdrResult($cdr);
        // Con CDR hay veredicto; con credenciales malas o SUNAT caído, preguntar el estado tampoco sirve.
        if ($fromCdr->outcome !== VerificationResult::UNAVAILABLE || self::isCredentialOrServiceError($cdr)) {
            return $fromCdr;
        }

        // Sin CDR (0125 «no se pudo obtener la constancia», 0127…): SUNAT igual puede decir si existe.
        return self::fromSunatStatusResult($this->sunat->getStatus($user, $pass, $ruc, $tipo, $serie, $numero));
    }

    public static function fromSunatCdrResult(StatusCdrResult $r): VerificationResult
    {
        if (!$r->isSuccess()) {
            $err = $r->getError();
            $code = $err !== null ? trim((string) $err->getCode()) : '';
            $msg = $err !== null ? trim((string) $err->getMessage()) : '';

            return VerificationResult::unavailable(self::explainError($code, $msg), VerificationResult::SOURCE_SUNAT_CDR, $code, $msg);
        }

        $cdrResponse = $r->getCdrResponse();
        if ($cdrResponse === null) {
            return VerificationResult::unavailable('SUNAT no devolvió el CDR.', VerificationResult::SOURCE_SUNAT_CDR, $r->getCode(), $r->getMessage());
        }

        $classified = SunatCdrClassifier::fromCdrResponse($cdrResponse);
        $out = new VerificationResult();
        $out->source = VerificationResult::SOURCE_SUNAT_CDR;
        $out->statusCode = $r->getCode();
        $out->statusMessage = $r->getMessage();
        $out->cdrCode = $classified['code'];
        $out->cdrMessage = $classified['message'];
        $out->cdrNotes = $classified['notes'];
        $out->cdrZip = $r->getCdrZip();
        $out->outcome = $classified['observed'] ? VerificationResult::OBSERVED
            : ($classified['success'] ? VerificationResult::ACCEPTED : VerificationResult::REJECTED);

        return $out;
    }

    public static function fromSunatStatusResult(StatusCdrResult $r): VerificationResult
    {
        if (!$r->isSuccess()) {
            $err = $r->getError();
            $code = $err !== null ? trim((string) $err->getCode()) : '';
            $msg = $err !== null ? trim((string) $err->getMessage()) : '';

            return VerificationResult::unavailable(self::explainError($code, $msg), VerificationResult::SOURCE_SUNAT_STATUS, $code, $msg);
        }

        $out = new VerificationResult();
        $out->source = VerificationResult::SOURCE_SUNAT_STATUS;
        $out->statusCode = $r->getCode();
        $out->statusMessage = $r->getMessage();
        $out->outcome = match (FiscalTransientErrorClassifier::normalizeCode($r->getCode())) {
            '0001' => VerificationResult::ACCEPTED,
            '0002' => VerificationResult::REJECTED,
            '0003' => VerificationResult::VOIDED,
            '0011' => VerificationResult::NOT_FOUND,
            default => VerificationResult::UNAVAILABLE,
        };
        if ($out->outcome === VerificationResult::UNAVAILABLE) {
            $out->reason = 'Respuesta de SUNAT sin conclusión: ' . trim($r->getCode() . ' ' . $r->getMessage());
        }
        if ($out->outcome === VerificationResult::ACCEPTED) {
            $out->reason = 'SUNAT lo confirma aceptado pero no entregó el CDR.';
        }

        return $out;
    }

    private function verifyWithValidaPse(FiscalDocument $doc, Empresa $empresa, string $ruc): VerificationResult
    {
        $token = $empresa->resolvePseToken();
        $base = $empresa->resolvePseBaseUrl();
        if ($token === '' || $base === '') {
            return VerificationResult::unavailable('La empresa PSE no tiene token o URL de ValidaPSE configurados.', VerificationResult::SOURCE_VALIDAPSE);
        }
        $filename = sprintf('%s-%s-%s-%s', $ruc, $doc->getDocumentType(), $doc->getSeries(), $doc->getNumber());
        $resp = $this->validaPse->consultar($base, $token, $filename);

        return self::fromValidaPseResponse($resp['http'], $resp['body'], $filename);
    }

    /**
     * ValidaPSE responde HTTP 202 con isSuccess:false cuando falla (prueba del 2026-10-18 con una
     * boleta y una factura aceptadas: «HTTP / Internal Server Error»). Solo hay veredicto si trae CDR.
     */
    public static function fromValidaPseResponse(int $http, string $body, string $filenameBase): VerificationResult
    {
        $json = json_decode($body, true);
        $resp = is_array($json) ? $json : [];
        $code = FiscalTransientErrorClassifier::pseCode($resp);
        $msg = PseResponseFormatter::message($resp);

        $cdrField = $resp['cdr'] ?? null;
        if (($resp['isSuccess'] ?? false) === true && is_string($cdrField) && $cdrField !== '') {
            $raw = base64_decode($cdrField, true);
            // ValidaPSE manda el CDR como XML en base64 (a veces zip); se guarda siempre como zip SUNAT.
            $zip = CdrNormalizer::decodePseCdrField($cdrField, $filenameBase);
            if ($raw !== false && $zip !== null) {
                $xml = str_starts_with($raw, 'PK') ? (self::xmlFromZip($zip) ?? '') : $raw;
                $cdrResponse = $xml !== '' ? (new DomCdrReader(new XmlReader()))->getCdrResponse($xml) : null;
                if ($cdrResponse !== null) {
                    $r = new StatusCdrResult();
                    $r->setSuccess(true)->setCode($code)->setMessage($msg)->setCdrZip($zip)->setCdrResponse($cdrResponse);
                    $out = self::fromSunatCdrResult($r);
                    $out->source = VerificationResult::SOURCE_VALIDAPSE;

                    return $out;
                }
            }
        }

        $reason = $resp === []
            ? 'ValidaPSE no respondió JSON (HTTP ' . $http . ').'
            : 'ValidaPSE no devolvió el CDR' . ($code !== null ? ' (' . $code . ')' : '') . ($msg !== '' ? ': ' . $msg : '') . '.';

        return VerificationResult::unavailable($reason, VerificationResult::SOURCE_VALIDAPSE, $code, $msg !== '' ? $msg : null);
    }

    private static function xmlFromZip(string $zip): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cdr');
        if ($tmp === false) {
            return null;
        }
        file_put_contents($tmp, $zip);
        $za = new \ZipArchive();
        $xml = null;
        if ($za->open($tmp) === true) {
            for ($i = 0; $i < $za->numFiles; $i++) {
                $name = (string) $za->getNameIndex($i);
                if (str_ends_with(strtolower($name), '.xml')) {
                    $xml = $za->getFromIndex($i) ?: null;
                    break;
                }
            }
            $za->close();
        }
        @unlink($tmp);

        return $xml;
    }

    private static function isCredentialOrServiceError(StatusCdrResult $r): bool
    {
        $err = $r->getError();
        if ($err === null) {
            return false;
        }
        $code = trim((string) $err->getCode());
        $msg = trim((string) $err->getMessage());

        return str_contains(mb_strtolower($msg), 'unauthorized')
            || in_array(FiscalTransientErrorClassifier::normalizeCode($code), ['0102', '0103', '0104', '0105', '0106', '0111'], true)
            || FiscalTransientErrorClassifier::isTransientSunatError($code, $msg);
    }

    private static function explainError(string $code, string $message): string
    {
        $m = mb_strtolower($message);
        if (str_contains($m, 'unauthorized') || in_array(FiscalTransientErrorClassifier::normalizeCode($code), ['0102', '0103', '0104', '0105', '0106'], true)) {
            return 'SUNAT rechazó el usuario SOL de la empresa (' . trim($code . ' ' . $message) . '). Revise el usuario secundario SOL con perfil de consulta.';
        }
        if (FiscalTransientErrorClassifier::isTransientSunatError($code, $message)) {
            return 'SUNAT no respondió (' . trim($code . ' ' . $message) . '). Intente más tarde.';
        }

        return 'SUNAT no pudo responder la consulta: ' . trim($code . ' ' . $message);
    }

    private static function messageFor(VerificationResult $r): string
    {
        $base = $r->cdrMessage ?: $r->statusMessage ?: $r->label();
        $tail = $r->cdrZip !== null && $r->cdrZip !== '' ? 'CDR recuperado' : 'sin CDR';

        return $base . ' [verificado en SUNAT, ' . $tail . ']';
    }

    private function rucOf(FiscalDocument $doc): string
    {
        $data = json_decode($doc->getSnapshotJson(), true);
        if (!is_array($data)) {
            return '';
        }
        if (isset($data['document']) && is_array($data['document'])) {
            $data = $data['document'];
        }

        return preg_replace('/\D/', '', (string) ($data['company']['ruc'] ?? $data['company_ruc'] ?? '')) ?? '';
    }
}
