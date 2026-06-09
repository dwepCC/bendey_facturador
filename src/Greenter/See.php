<?php

declare(strict_types=1);

namespace App\Greenter;

use App\Greenter\Factory\XmlBuilderResolverFactory;
use Greenter\Factory\WsSenderResolver;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\BaseResult;
use Greenter\See as GreenterSee;
use Greenter\Validator\ErrorCodeProviderInterface;
use Greenter\Ws\Services\SoapClient;

/**
 * See (SOAP legacy) con XmlBuilderResolver del proyecto para Despatch/GRE.
 */
class See extends GreenterSee
{
    public function __construct(
        private readonly XmlBuilderResolverFactory $xmlBuilderResolverFactory,
    ) {
        parent::__construct();
    }

    public function getXmlSigned(DocumentInterface $document): ?string
    {
        $buildResolver = $this->xmlBuilderResolverFactory->create($this->resolveBuilderOptions());

        return $this->getFactory()
            ->setBuilder($buildResolver->find(get_class($document)))
            ->getXmlSigned($document);
    }

    public function send(DocumentInterface $document): ?BaseResult
    {
        $this->configureFactoryWithOverride(get_class($document));

        return $this->getFactory()->send($document);
    }

    public function sendXml(string $type, string $name, string $xml): ?BaseResult
    {
        $this->configureFactoryWithOverride($type);

        return $this->getFactory()->sendXml($name, $xml);
    }

    private function configureFactoryWithOverride(string $docClass): void
    {
        $buildResolver = $this->xmlBuilderResolverFactory->create($this->resolveBuilderOptions());
        $senderResolver = new WsSenderResolver($this->resolveWsClient(), $this->resolveCodeProvider());

        $this->getFactory()
            ->setBuilder($buildResolver->find($docClass))
            ->setSender($senderResolver->find($docClass));
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBuilderOptions(): array
    {
        $ref = new \ReflectionProperty(GreenterSee::class, 'options');
        $ref->setAccessible(true);

        return (array) $ref->getValue($this);
    }

    private function resolveWsClient(): SoapClient
    {
        $ref = new \ReflectionProperty(GreenterSee::class, 'wsClient');
        $ref->setAccessible(true);

        return $ref->getValue($this);
    }

    private function resolveCodeProvider(): ?ErrorCodeProviderInterface
    {
        $ref = new \ReflectionProperty(GreenterSee::class, 'codeProvider');
        $ref->setAccessible(true);

        return $ref->getValue($this);
    }
}
