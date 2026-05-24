<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Modelo fiscal unificado por empresa (SSOT).
 * - connection_type, pse_base_url, pse_token, certificate_password
 * - pse_secondary_user, pse_metadata_json
 * - connection_status, connection_error, last_connection_check
 */
final class Version20260528000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Empresa: campos fiscales unificados (PSE extensible, estado conexión)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE empresa ADD connection_type VARCHAR(20) DEFAULT 'bearer' NOT NULL");
        $this->addSql('ALTER TABLE empresa ADD pse_base_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD pse_token VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD certificate_password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD pse_secondary_user VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD pse_metadata_json LONGTEXT DEFAULT NULL');
        $this->addSql("ALTER TABLE empresa ADD connection_status VARCHAR(30) DEFAULT 'configuration_missing' NOT NULL");
        $this->addSql('ALTER TABLE empresa ADD connection_error LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD last_connection_check DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Migración legacy: pse_pass → pse_token
        $this->addSql('UPDATE empresa SET pse_token = pse_pass WHERE pse_token IS NULL AND pse_pass IS NOT NULL AND pse_pass != \'\'');

        // Mapeo send_mode legacy
        $this->addSql("UPDATE empresa SET send_mode = 'sunat_direct' WHERE send_mode = '' OR send_mode IS NULL");
        $this->addSql("UPDATE empresa SET provider = 'sunat' WHERE send_mode = 'sunat_direct' AND (provider IS NULL OR provider = '' OR provider = 'sunat_direct')");
        $this->addSql("UPDATE empresa SET provider = 'validapse' WHERE send_mode = 'pse' AND (provider IS NULL OR provider = '' OR provider = 'pse')");

        // Estado inicial según credenciales existentes
        $this->addSql("UPDATE empresa SET connection_status = 'connected' WHERE send_mode = 'pse' AND pse_token IS NOT NULL AND pse_token != ''");
        $this->addSql("UPDATE empresa SET connection_status = 'connected' WHERE send_mode = 'sunat_direct' AND sol_user != '' AND sol_pass != '' AND certificate IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empresa DROP connection_type');
        $this->addSql('ALTER TABLE empresa DROP pse_base_url');
        $this->addSql('ALTER TABLE empresa DROP pse_token');
        $this->addSql('ALTER TABLE empresa DROP certificate_password');
        $this->addSql('ALTER TABLE empresa DROP pse_secondary_user');
        $this->addSql('ALTER TABLE empresa DROP pse_metadata_json');
        $this->addSql('ALTER TABLE empresa DROP connection_status');
        $this->addSql('ALTER TABLE empresa DROP connection_error');
        $this->addSql('ALTER TABLE empresa DROP last_connection_check');
    }
}
