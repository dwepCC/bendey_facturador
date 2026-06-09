<?php

declare(strict_types=1);

namespace App\Greenter;

use App\Greenter\Factory\XmlBuilderResolverFactory;

/**
 * Factory para App\Greenter\Api (GRE REST con override de plantillas).
 */
final class ApiFactory
{
    public function __construct(
        private readonly XmlBuilderResolverFactory $xmlBuilderResolverFactory,
    ) {
    }

    /**
     * @param array{auth: string, cpe: string} $endpoints
     */
    public function create(array $endpoints, string $cachePath): Api
    {
        $api = new Api($this->xmlBuilderResolverFactory, $endpoints);
        $api->setBuilderOptions(['cache' => $cachePath, 'autoescape' => false]);

        return $api;
    }
}
