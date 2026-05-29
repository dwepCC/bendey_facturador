<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Rutas de assets estáticos del panel fiscal con cache-busting (filemtime).
 */
final class FiscalUiAssets
{
    public static function stylesheetHref(): string
    {
        $path = dirname(__DIR__, 2) . '/public/css/fiscal-ui.css';
        $v = is_file($path) ? (string) filemtime($path) : '1';

        return '/css/fiscal-ui.css?v=' . $v;
    }
}
