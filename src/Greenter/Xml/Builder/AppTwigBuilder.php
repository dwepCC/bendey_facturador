<?php

declare(strict_types=1);

namespace App\Greenter\Xml\Builder;

use Greenter\Model\TimeZonePe;
use Greenter\Xml\Filter\FormatFilter;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Twig builder con ChainLoader: plantillas del proyecto primero, Greenter vendor después.
 */
class AppTwigBuilder
{
    protected Environment $twig;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options, string $overrideTemplatesPath, string $vendorTemplatesPath)
    {
        $this->twig = $this->createTwig($options, $overrideTemplatesPath, $vendorTemplatesPath);
    }

    public function render(string $template, object $doc): string
    {
        return $this->twig->render($template, [
            'doc' => $doc,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createTwig(array $options, string $overrideTemplatesPath, string $vendorTemplatesPath): Environment
    {
        $loaders = [];

        if ($overrideTemplatesPath !== '' && is_dir($overrideTemplatesPath)) {
            $loaders[] = new FilesystemLoader($overrideTemplatesPath);
        }

        $loaders[] = new FilesystemLoader($vendorTemplatesPath);

        $loader = count($loaders) === 1 ? $loaders[0] : new ChainLoader($loaders);

        $twigEnv = new Environment($loader, $options);
        $this->loadFilterAndFunctions($twigEnv);
        $this->configureTimezone($twigEnv);

        return $twigEnv;
    }

    private function configureTimezone(Environment $twig): void
    {
        $extension = $twig->getExtension(CoreExtension::class);
        if ($extension instanceof CoreExtension) {
            $extension->setTimezone(TimeZonePe::DEFAULT);
        }
    }

    private function loadFilterAndFunctions(Environment $twig): void
    {
        $formatFilter = new FormatFilter();

        $twig->addFilter(new TwigFilter('n_format', [$formatFilter, 'number']));
        $twig->addFilter(new TwigFilter('n_format_limit', [$formatFilter, 'numberLimit']));
    }
}
