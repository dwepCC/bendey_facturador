<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fase 2: fingerprint único, versionado snapshot, attempts, email logs, PSE response.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fiscal_documents ADD fiscal_fingerprint VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE fiscal_documents ADD provider VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE fiscal_documents ADD snapshot_version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE fiscal_documents ADD schema_version VARCHAR(20) DEFAULT \'1.0\' NOT NULL');
        $this->addSql('ALTER TABLE fiscal_documents ADD greenter_version VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE fiscal_documents ADD provider_version VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE fiscal_documents ADD pse_response_json LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE fiscal_documents ADD unsigned_xml_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FISCAL_FINGERPRINT ON fiscal_documents (fiscal_fingerprint)');

        $this->addSql('CREATE TABLE fiscal_emit_attempts (
            id INT AUTO_INCREMENT NOT NULL,
            document_uuid CHAR(36) NOT NULL,
            attempt_number INT NOT NULL,
            provider VARCHAR(50) DEFAULT NULL,
            status VARCHAR(30) NOT NULL,
            sunat_code VARCHAR(20) DEFAULT NULL,
            sunat_message LONGTEXT DEFAULT NULL,
            pse_message LONGTEXT DEFAULT NULL,
            error_message LONGTEXT DEFAULT NULL,
            duration_ms INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_FISCAL_ATTEMPT_DOC (document_uuid),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE outbound_email_logs (
            id INT AUTO_INCREMENT NOT NULL,
            document_uuid CHAR(36) NOT NULL,
            recipient_email VARCHAR(255) NOT NULL,
            status VARCHAR(30) DEFAULT \'pending\' NOT NULL,
            attempts INT DEFAULT 0 NOT NULL,
            error_message LONGTEXT DEFAULT NULL,
            provider_response LONGTEXT DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_EMAIL_DOC (document_uuid),
            INDEX IDX_EMAIL_STATUS (status),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE fiscal_webhook_events (
            id INT AUTO_INCREMENT NOT NULL,
            event_key VARCHAR(128) NOT NULL,
            document_uuid CHAR(36) NOT NULL,
            payload_hash VARCHAR(64) NOT NULL,
            delivered_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_WEBHOOK_EVENT (event_key),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fiscal_webhook_events');
        $this->addSql('DROP TABLE outbound_email_logs');
        $this->addSql('DROP TABLE fiscal_emit_attempts');
        $this->addSql('DROP INDEX UNIQ_FISCAL_FINGERPRINT ON fiscal_documents');
        $this->addSql('ALTER TABLE fiscal_documents DROP fiscal_fingerprint, DROP provider, DROP snapshot_version, DROP schema_version, DROP greenter_version, DROP provider_version, DROP pse_response_json, DROP unsigned_xml_url');
    }
}
