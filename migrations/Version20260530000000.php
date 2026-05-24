<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V2.1 — Observabilidad fiscal: audit logs, alertas y métricas tenant.
 */
final class Version20260530000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fiscal observability V2.1: fiscal_audit_logs, fiscal_alerts, fiscal_tenant_metrics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE fiscal_audit_logs (
    id BIGINT AUTO_INCREMENT NOT NULL,
    tenant_id INT DEFAULT NULL,
    tenant_slug VARCHAR(100) DEFAULT NULL,
    ruc VARCHAR(11) DEFAULT NULL,
    company_id INT DEFAULT NULL,
    document_uuid VARCHAR(36) DEFAULT NULL,
    document_type VARCHAR(10) DEFAULT NULL,
    series VARCHAR(10) DEFAULT NULL,
    number VARCHAR(20) DEFAULT NULL,
    sale_id INT DEFAULT NULL,
    external_id VARCHAR(100) DEFAULT NULL,
    send_mode VARCHAR(30) DEFAULT NULL,
    provider VARCHAR(50) DEFAULT NULL,
    connection_type VARCHAR(20) DEFAULT NULL,
    event_type VARCHAR(50) NOT NULL,
    status VARCHAR(30) NOT NULL,
    attempt INT DEFAULT NULL,
    request_id VARCHAR(36) DEFAULT NULL,
    queue_job_id VARCHAR(100) DEFAULT NULL,
    error_code VARCHAR(50) DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    error_stack LONGTEXT DEFAULT NULL,
    metadata_json LONGTEXT DEFAULT NULL,
    started_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    finished_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    duration_ms INT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX idx_fal_tenant_slug (tenant_slug),
    INDEX idx_fal_ruc (ruc),
    INDEX idx_fal_document_uuid (document_uuid),
    INDEX idx_fal_status (status),
    INDEX idx_fal_event_type (event_type),
    INDEX idx_fal_created_at (created_at),
    INDEX idx_fal_tenant_created (tenant_slug, created_at),
    INDEX idx_fal_provider_created (provider, created_at),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE fiscal_alerts (
    id INT AUTO_INCREMENT NOT NULL,
    tenant_id INT DEFAULT NULL,
    tenant_slug VARCHAR(100) DEFAULT NULL,
    ruc VARCHAR(11) DEFAULT NULL,
    alert_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'warning',
    message VARCHAR(500) NOT NULL,
    metadata_json LONGTEXT DEFAULT NULL,
    acknowledged_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX idx_fa_tenant (tenant_slug),
    INDEX idx_fa_type (alert_type),
    INDEX idx_fa_severity (severity),
    INDEX idx_fa_open (resolved_at, created_at),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE fiscal_tenant_metrics (
    id INT AUTO_INCREMENT NOT NULL,
    tenant_id INT NOT NULL,
    tenant_slug VARCHAR(100) NOT NULL,
    ruc VARCHAR(11) DEFAULT NULL,
    period_date DATE NOT NULL,
    period_type VARCHAR(10) NOT NULL DEFAULT 'day',
    documents_emitted INT NOT NULL DEFAULT 0,
    documents_accepted INT NOT NULL DEFAULT 0,
    errors INT NOT NULL DEFAULT 0,
    retries INT NOT NULL DEFAULT 0,
    avg_duration_ms INT DEFAULT NULL,
    success_rate NUMERIC(5, 2) DEFAULT NULL,
    provider VARCHAR(50) DEFAULT NULL,
    send_mode VARCHAR(30) DEFAULT NULL,
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    UNIQUE INDEX uniq_ftm_tenant_period (tenant_id, period_date, period_type, provider),
    INDEX idx_ftm_slug_date (tenant_slug, period_date),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fiscal_tenant_metrics');
        $this->addSql('DROP TABLE fiscal_alerts');
        $this->addSql('DROP TABLE fiscal_audit_logs');
    }
}
