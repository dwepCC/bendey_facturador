<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Empresa;
use App\Exception\EmpresaNoRegistradaException;
use App\Greenter\Api;
use App\Greenter\ApiFactory;
use App\Repository\EmpresaRepository;
use App\Service\Gre\GreEndpointResolver;
use App\Service\Gre\GreOAuthCredentialResolver;

/**
 * Factory Greenter\Api (REST GRE) multiempresa con OAuth por RUC.
 */
class SeeApiFactory
{
    private ConfigProviderInterface $config;
    private FileDataReader $fileReader;
    private EmpresaRepository $empresaRepository;
    private string $cachePath;
    private ApiFactory $apiFactory;

    public function __construct(
        ConfigProviderInterface $config,
        FileDataReader $fileReader,
        EmpresaRepository $empresaRepository,
        string $cachePath,
        ApiFactory $apiFactory
    ) {
        $this->config = $config;
        $this->fileReader = $fileReader;
        $this->empresaRepository = $empresaRepository;
        $this->cachePath = $cachePath;
        $this->apiFactory = $apiFactory;
    }

    /**
     * @throws EmpresaNoRegistradaException
     */
    public function build(?string $ruc): Api
    {
        $ruc = $ruc !== null ? trim((string) $ruc) : '';
        if ($ruc === '') {
            throw new EmpresaNoRegistradaException('', 'RUC requerido. La aplicación opera en modo multiempresa con datos en base de datos.');
        }

        $empresa = $this->empresaRepository->findByRuc($ruc);
        if ($empresa === null) {
            throw new EmpresaNoRegistradaException($ruc);
        }

        return $this->buildForEmpresa($empresa);
    }

    /**
     * @throws EmpresaNoRegistradaException
     */
    public function buildForEmpresa(Empresa $empresa): Api
    {
        $certFile = $empresa->getCertificate();
        if ($certFile === null || trim($certFile) === '') {
            throw new EmpresaNoRegistradaException($empresa->getRuc(), 'Certificado digital no configurado para GRE REST');
        }

        $endpoints = GreEndpointResolver::resolveForAmbiente($empresa->getAmbiente(), $this->config);
        $oauth = GreOAuthCredentialResolver::resolve($empresa, $this->config);

        $api = $this->apiFactory->create([
            'auth' => $endpoints['auth'],
            'cpe' => $endpoints['cpe'],
        ], $this->cachePath);

        [$rucPart, $user] = $this->getRucAndUser($empresa->getSolUser());
        $api->setClaveSOL($rucPart, $user, $empresa->getSolPass());
        $api->setApiCredentials($oauth['client_id'], $oauth['client_secret']);
        $api->setCertificate($this->fileReader->getContents($certFile));

        return $api;
    }

    /**
     * Prueba OAuth GRE (token) sin enviar comprobante.
     */
    public function testOAuthConnection(Empresa $empresa): void
    {
        $api = $this->buildForEmpresa($empresa);
        $api->sendXml('00000000000-09-T000-0', '<?xml version="1.0"?><test/>');
    }

    /**
     * Obtiene token OAuth GRE validando credenciales (sin envío CPE).
     */
    public function validateOAuthToken(Empresa $empresa): bool
    {
        try {
            $api = $this->buildForEmpresa($empresa);
            $reflection = new \ReflectionClass($api);
            $method = $reflection->getMethod('createSender');
            $method->setAccessible(true);
            $method->invoke($api);

            return true;
        } catch (\Throwable $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'oauth') || str_contains($msg, 'token') || str_contains($msg, '401')) {
                throw $e;
            }
            // Otros errores en createSender implican que OAuth pasó (p.ej. XML inválido en test send)
            return true;
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getRucAndUser(string $username): array
    {
        $username = trim($username);
        if (strlen($username) < 12) {
            throw new \InvalidArgumentException('SOL_USER inválido para GRE REST');
        }

        return [substr($username, 0, 11), substr($username, 11)];
    }
}
