<?php

declare(strict_types=1);

namespace App\Service\Fiscal\EmpresaDestroy;

use App\Entity\Empresa;

/**
 * Elimina archivos locales de certificados y comprobantes fiscales de una empresa.
 */
class EmpresaDestroyStoragePurgeService
{
    public function __construct(
        private readonly string $dataPath,
        private readonly string $fiscalStoragePath,
    ) {
    }

    /**
     * @return array{removed: list<string>, errors: list<string>, residues_found: list<string>}
     */
    public function purge(Empresa $empresa): array
    {
        return $this->purgeByContext(EmpresaDestroyPurgeContext::fromEmpresa($empresa));
    }

    /**
     * Purga por RUC/slug sin requerir entidad Empresa (idempotencia / residuos).
     *
     * @return array{removed: list<string>, errors: list<string>, residues_found: list<string>}
     */
    public function purgeByContext(EmpresaDestroyPurgeContext $ctx): array
    {
        $removed = [];
        $errors = [];

        foreach ($this->resolvePaths($ctx) as $path) {
            $this->removePath($path, $removed, $errors);
        }

        $residues = $this->findResidues($ctx);

        return [
            'removed' => $removed,
            'errors' => $errors,
            'residues_found' => $residues,
        ];
    }

    /**
     * @return list<string>
     */
    public function findResidues(EmpresaDestroyPurgeContext $ctx): array
    {
        $residues = [];
        foreach ($this->resolvePaths($ctx) as $path) {
            if ($path !== '' && file_exists($path)) {
                $residues[] = $path;
            }
        }

        return $residues;
    }

    /**
     * @return list<string>
     */
    private function resolvePaths(EmpresaDestroyPurgeContext $ctx): array
    {
        $paths = [];
        $data = rtrim($this->dataPath, '/\\');
        $ruc = trim($ctx->ruc);

        if ($ruc !== '') {
            $paths[] = $data . DIRECTORY_SEPARATOR . $ruc . '-cert.pem';
            $paths[] = $data . DIRECTORY_SEPARATOR . $ruc . '-logo.png';
        }

        $cert = trim((string) ($ctx->certificateFile ?? ''));
        if ($cert !== '' && basename($cert) === $cert) {
            $paths[] = $data . DIRECTORY_SEPARATOR . $cert;
        }

        $logo = trim((string) ($ctx->logoFile ?? ''));
        if ($logo !== '' && basename($logo) === $logo) {
            $paths[] = $data . DIRECTORY_SEPARATOR . $logo;
        }

        $slug = trim((string) ($ctx->tenantSlug ?? ''));
        if ($slug !== '') {
            $safe = preg_replace('/[^a-zA-Z0-9_-]/', '-', $slug) ?: 'unknown';
            $paths[] = rtrim($this->fiscalStoragePath, '/\\') . DIRECTORY_SEPARATOR . $safe;
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param list<string> $removed
     * @param list<string> $errors
     */
    private function removePath(string $path, array &$removed, array &$errors): void
    {
        if ($path === '' || !file_exists($path)) {
            return;
        }
        if (is_dir($path)) {
            if ($this->removeDirectory($path)) {
                $removed[] = $path;
            } else {
                $errors[] = 'No se pudo eliminar directorio: ' . $path;
            }
            return;
        }
        if (@unlink($path)) {
            $removed[] = $path;
        } else {
            $errors[] = 'No se pudo eliminar archivo: ' . $path;
        }
    }

    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        $items = scandir($dir);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($full)) {
                if (!$this->removeDirectory($full)) {
                    return false;
                }
            } elseif (!@unlink($full)) {
                return false;
            }
        }

        return @rmdir($dir);
    }
}
