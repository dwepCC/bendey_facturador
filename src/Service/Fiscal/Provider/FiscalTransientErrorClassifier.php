<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

/**
 * Distingue "SUNAT/PSE no pudo atender" de "SUNAT evaluó el comprobante y lo rechazó".
 *
 * Caso real 2026-10-18 (comercializadoradecalzadosadri): B003-894 volvió de ValidaPSE con
 * code 0109 "El servicio de autenticación no está disponible" y B003-895 con {"message":"Server
 * Error"}. Las dos quedaban `rejected`, y un rechazo obliga a re-emitir con número nuevo aunque
 * SUNAT nunca haya visto el comprobante. Un fallo de servicio no es un veredicto: se reintenta
 * con el mismo número y, si se agotan los intentos, queda `error` (reenviable), nunca `rejected`.
 *
 * ValidaPSE responde `estado: 501` para CUALQUIER fallo, incluidos rechazos reales (1033, 2xxx,
 * 3xxx), así que `estado` no sirve para clasificar: manda `code` (el código SUNAT, o "HTTP" cuando
 * SUNAT respondió un error HTTP sin evaluar el XML).
 */
final class FiscalTransientErrorClassifier
{
    /**
     * Catálogo SUNAT de excepciones del servicio (0100–0999) que significan "no pude procesar",
     * no "el comprobante está mal". Los de credenciales/perfil (0101–0106, 0110–0113) quedan
     * fuera: reintentar no los arregla.
     */
    private const TRANSIENT_SUNAT_CODES = [
        '0100', // El sistema no puede responder su solicitud. Intente nuevamente…
        '0109', // … (El servicio de autenticación no está disponible)
        '0130', // … (No se pudo obtener el ticket de proceso)
        '0131', // … (No se pudo grabar el archivo en el directorio)
        '0132', // … (No se pudo grabar escribir en el archivo zip)
        '0133', // … (No se pudo grabar la entrada del log)
        '0134', // … (No se pudo grabar en el storage)
        '0135', // … (No se pudo encolar el pedido)
        '0136', // … (No se pudo recibir una respuesta del batch)
        '0137', // … (Se obtuvo una respuesta nula)
        '0138', // … (Error en Base de Datos)
        '0200', // No se pudo procesar su solicitud. (Ocurrió un error en el batch)
        '0201', // … (Llegó un requerimiento nulo al batch)
        '0251', // … (No se pudo crear un directorio para el unzip)
        '0253', // … (No se pudo comprimir la constancia)
    ];

    /** Códigos no numéricos que Greenter usa cuando no hubo respuesta SOAP evaluable. */
    private const TRANSIENT_NON_NUMERIC_CODES = ['HTTP', 'HTTPS', 'SOAP', 'TIMEOUT'];

    private const TRANSIENT_MESSAGE_PATTERNS = [
        '/el sistema no puede responder su solicitud/u',
        '/no se pudo procesar su solicitud\. \((ocurri[oó] un error en el batch|lleg[oó] un requerimiento nulo)/u',
        '/servicio[^.]{0,60}no (est[aá] )?disponible/u',
        '/^server error$/u',
        '/internal server error/u',
        '/service (temporarily )?unavailable/u',
        '/bad gateway/u',
        '/gateway time-?out/u',
        '/timed out|time-?out/u',
        '/could not connect|connection (refused|reset)|failed to connect/u',
    ];

    /**
     * Normaliza un código SUNAT: "109" → "0109", "soap-env:Client.0109" → "0109".
     * Devuelve el código en mayúsculas si no es numérico ("HTTP"), o null si está vacío.
     */
    public static function normalizeCode(?string $code): ?string
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }
        if (preg_match('/(\d{1,4})$/', $code, $m) === 1) {
            return str_pad($m[1], 4, '0', STR_PAD_LEFT);
        }

        return strtoupper($code);
    }

    public static function isTransientSunatCode(?string $code): bool
    {
        $normalized = self::normalizeCode($code);
        if ($normalized === null) {
            return false;
        }

        return in_array($normalized, self::TRANSIENT_SUNAT_CODES, true)
            || in_array($normalized, self::TRANSIENT_NON_NUMERIC_CODES, true);
    }

    public static function isTransientMessage(?string $message): bool
    {
        $m = mb_strtolower(trim((string) $message));
        if ($m === '') {
            return false;
        }
        foreach (self::TRANSIENT_MESSAGE_PATTERNS as $pattern) {
            if (preg_match($pattern, $m) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Error de SUNAT directo (fault SOAP sin CDR, o error de consulta de ticket).
     * Un código SUNAT de contenido manda sobre el texto: si SUNAT dio un código que no es de
     * servicio, es un veredicto.
     */
    public static function isTransientSunatError(?string $code, ?string $message): bool
    {
        $normalized = self::normalizeCode($code);
        if ($normalized !== null && $normalized !== 'ERROR') {
            return self::isTransientSunatCode($normalized);
        }

        return self::isTransientMessage($message);
    }

    /**
     * Respuesta del PSE (ValidaPSE y compatibles).
     *
     * @param array<string, mixed> $resp JSON decodificado (vacío si el cuerpo no era JSON)
     * @param int $httpCode 0 = sin respuesta (timeout, conexión caída)
     */
    public static function isTransientPseResponse(int $httpCode, array $resp, string $rawBody = ''): bool
    {
        if (($resp['isSuccess'] ?? false) === true) {
            return false;
        }

        $code = self::pseCode($resp);
        if ($code !== null) {
            // Código explícito: es lo único confiable. 1033, 2xxx, 3xxx… son veredictos.
            return self::isTransientSunatCode($code);
        }

        if ($httpCode === 0 || $httpCode >= 500) {
            return true;
        }

        $message = PseResponseFormatter::message($resp);
        if ($message === '' && is_string($resp['errores'] ?? null)) {
            $message = (string) $resp['errores'];
        }
        if ($message === '' && $resp === []) {
            $message = $rawBody;
        }

        return self::isTransientMessage($message);
    }

    /**
     * @param array<string, mixed> $resp
     */
    public static function pseCode(array $resp): ?string
    {
        foreach (['code', 'codigo', 'sunat_code', 'codigo_sunat'] as $key) {
            $value = $resp[$key] ?? null;
            if (is_int($value) || (is_string($value) && trim($value) !== '')) {
                return self::normalizeCode((string) $value);
            }
        }

        return null;
    }
}
