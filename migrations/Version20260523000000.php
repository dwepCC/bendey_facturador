<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Config fiscal SaaS por tenant + tabla fiscal_documents (snapshot inmutable).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empresa ADD tenant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD tenant_slug VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD send_mode VARCHAR(20) DEFAULT \'sunat_direct\' NOT NULL');
        $this->addSql('ALTER TABLE empresa ADD provider VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD pse_user VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD pse_pass VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD automatic_send TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE empresa ADD email_enabled TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE empresa ADD retry_enabled TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE empresa ADD enabled TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('CREATE INDEX IDX_EMPRESA_TENANT_SLUG ON empresa (tenant_slug)');
        $this->addSql('CREATE INDEX IDX_EMPRESA_TENANT_ID ON empresa (tenant_id)');

        $this->addSql('CREATE TABLE fiscal_documents (
            id INT AUTO_INCREMENT NOT NULL,
            document_uuid CHAR(36) NOT NULL,
            tenant_id INT NOT NULL,
            tenant_slug VARCHAR(100) NOT NULL,
            sale_id INT NOT NULL,
            document_type VARCHAR(10) NOT NULL,
            series VARCHAR(10) NOT NULL,
            number VARCHAR(20) NOT NULL,
            status VARCHAR(30) DEFAULT \'pending\' NOT NULL,
            send_mode VARCHAR(20) DEFAULT NULL,
            sunat_mode VARCHAR(20) DEFAULT NULL,
            customer_email VARCHAR(255) DEFAULT NULL,
            queued_at DATETIME DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            accepted_at DATETIME DEFAULT NULL,
            rejected_at DATETIME DEFAULT NULL,
            xml_url VARCHAR(500) DEFAULT NULL,
            xml_signed_url VARCHAR(500) DEFAULT NULL,
            cdr_url VARCHAR(500) DEFAULT NULL,
            pdf_url VARCHAR(500) DEFAULT NULL,
            hash VARCHAR(500) DEFAULT NULL,
            ticket VARCHAR(100) DEFAULT NULL,
            sunat_code VARCHAR(20) DEFAULT NULL,
            sunat_message LONGTEXT DEFAULT NULL,
            retry_count INT DEFAULT 0 NOT NULL,
            next_retry_at DATETIME DEFAULT NULL,
            email_status VARCHAR(30) DEFAULT NULL,
            snapshot_json LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_FISCAL_DOC_UUID (document_uuid),
            INDEX IDX_FISCAL_TENANT_STATUS (tenant_slug, status),
            INDEX IDX_FISCAL_SALE (tenant_id, sale_id),
            INDEX IDX_FISCAL_CREATED (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fiscal_documents');
        $this->addSql('DROP INDEX IDX_EMPRESA_TENANT_SLUG ON empresa');
        $this->addSql('DROP INDEX IDX_EMPRESA_TENANT_ID ON empresa');
        $this->addSql('ALTER TABLE empresa DROP tenant_id, DROP tenant_slug, DROP send_mode, DROP provider, DROP pse_user, DROP pse_pass, DROP automatic_send, DROP email_enabled, DROP retry_enabled, DROP enabled');
    }
}
