<?php

declare(strict_types=1);

namespace App\Greenter\Xml\Builder;

use Greenter\Builder\BuilderInterface;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\DocumentInterface;

/**
 * Builder Despatch con override de plantilla GRE2022 (cac:DespatchParty para tipo 31).
 */
final class DespatchBuilder extends AppTwigBuilder implements BuilderInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options, string $overrideTemplatesPath, string $vendorTemplatesPath)
    {
        parent::__construct($options, $overrideTemplatesPath, $vendorTemplatesPath);
    }

    public function build(DocumentInterface $document): ?string
    {
        /** @var Despatch $despatch */
        $despatch = $document;
        $template = $despatch->getVersion() === '2022' ? 'despatch2022.xml.twig' : 'despatch.xml.twig';

        return $this->render($template, $document);
    }
}
