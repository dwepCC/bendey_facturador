<?php

declare(strict_types=1);

namespace App\Greenter\Factory;

use App\Greenter\Xml\Builder\DespatchBuilder;
use Greenter\Builder\BuilderInterface;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Voided\Reversion;
use Greenter\Xml\Builder\VoidedBuilder;

/**
 * Resolver XML con DespatchBuilder custom para GRE transportista (31).
 */
final class XmlBuilderResolver
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly array $options,
        private readonly string $overrideTemplatesPath,
        private readonly string $vendorTemplatesPath,
    ) {
    }

    public function find(string $docClass): BuilderInterface
    {
        if ($docClass === Despatch::class) {
            return new DespatchBuilder($this->options, $this->overrideTemplatesPath, $this->vendorTemplatesPath);
        }

        $builderClass = $this->findBuilderType($docClass);

        return new $builderClass($this->options);
    }

    private function findBuilderType(string $docClass): string
    {
        if ($docClass === Reversion::class) {
            return VoidedBuilder::class;
        }

        $className = substr(strrchr($docClass, '\\'), 1);

        return 'Greenter\\Xml\\Builder\\' . $className . 'Builder';
    }
}
