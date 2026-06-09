<?php

declare(strict_types=1);

namespace App\Greenter;

use App\Greenter\Factory\XmlBuilderResolverFactory;
use Greenter\Api as GreenterApi;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\BaseResult;
use Greenter\XMLSecLibs\Sunat\SignedXml;

/**
 * GRE REST Api con XmlBuilderResolver del proyecto (override GRE31).
 */
class Api extends GreenterApi
{
    public function __construct(
        private readonly XmlBuilderResolverFactory $xmlBuilderResolverFactory,
        ?array $endpoints = null,
        ?\Greenter\Api\ApiFactory $factory = null,
        ?SignedXml $signer = null,
    ) {
        parent::__construct($endpoints, $factory, $signer);
    }

    public function send(DocumentInterface $document): ?BaseResult
    {
        $buildResolver = $this->xmlBuilderResolverFactory->create($this->resolveBuilderOptions());
        $builder = $buildResolver->find(get_class($document));

        $xml = $builder->build($document);

        $refLastXml = new \ReflectionProperty(GreenterApi::class, 'lastXml');
        $refLastXml->setAccessible(true);
        $refLastXml->setValue($this, $this->resolveSigner()->signXml($xml));

        return $this->sendXml($document->getName(), (string) $refLastXml->getValue($this));
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBuilderOptions(): array
    {
        $ref = new \ReflectionProperty(GreenterApi::class, 'options');
        $ref->setAccessible(true);

        return (array) $ref->getValue($this);
    }

    private function resolveSigner(): SignedXml
    {
        $ref = new \ReflectionProperty(GreenterApi::class, 'signer');
        $ref->setAccessible(true);

        return $ref->getValue($this);
    }
}
