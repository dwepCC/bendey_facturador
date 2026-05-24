<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

/**
 * Resultado de validación de conexión fiscal.
 */
class FiscalConnectionResult
{
    public bool $success = false;
    public string $status = 'error';
    public ?string $message = null;

    public static function ok(string $message = 'Conexión válida'): self
    {
        $r = new self();
        $r->success = true;
        $r->status = 'connected';
        $r->message = $message;
        return $r;
    }

    public static function fail(string $status, string $message): self
    {
        $r = new self();
        $r->success = false;
        $r->status = $status;
        $r->message = $message;
        return $r;
    }
}
