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

    public static function dashNavHtml(string $active): string
    {
        $links = [
            'dashboard' => ['/dashboard', 'Comprobantes'],
            'empresas' => ['/dashboard/empresas', 'Empresas'],
        ];
        $html = '<nav class="dash-nav" aria-label="Secciones del panel">';
        foreach ($links as $key => [$href, $label]) {
            $class = $key === $active ? ' is-active' : '';
            $html .= sprintf(
                '<a href="%s" class="dash-nav-link%s">%s</a>',
                htmlspecialchars($href, ENT_QUOTES),
                $class,
                htmlspecialchars($label, ENT_QUOTES)
            );
        }
        $html .= '</nav>';

        return $html;
    }
}
