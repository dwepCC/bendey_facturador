<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Credenciales de la API REST de validez de SUNAT (Consulta Integrada) por empresa.
 */
final class Version20261018010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'API de validez SUNAT: cpe_api_client_id, cpe_api_client_secret, cpe_api_configured_at en empresa';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empresa ADD cpe_api_client_id VARCHAR(120) DEFAULT NULL, ADD cpe_api_client_secret VARCHAR(255) DEFAULT NULL, ADD cpe_api_configured_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empresa DROP cpe_api_client_id, DROP cpe_api_client_secret, DROP cpe_api_configured_at');
    }
}
