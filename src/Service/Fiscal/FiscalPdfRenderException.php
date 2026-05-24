<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

/**
 * Error explícito al generar PDF fiscal (wkhtmltopdf / plantilla).
 */
class FiscalPdfRenderException extends \RuntimeException
{
}
