<?php

declare(strict_types=1);

namespace App\Greenter\Factory;

/**
 * Crea resolvers de builders XML con plantillas override del proyecto (GRE31 / error 3383).
 */
final class XmlBuilderResolverFactory
{
    public function __construct(
        private readonly string $overrideTemplatesPath,
        private readonly string $vendorTemplatesPath,
    ) {
    }

    /**
     * @param array<string, mixed> $options Opciones Twig (cache, autoescape, etc.)
     */
    public function create(array $options = []): XmlBuilderResolver
    {
        return new XmlBuilderResolver($options, $this->overrideTemplatesPath, $this->vendorTemplatesPath);
    }
}
