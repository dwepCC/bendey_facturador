<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Webhook audit columns + fiscal document indexes for dashboard filters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fiscal_webhook_events ADD http_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fiscal_webhook_events ADD response_body LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_fiscal_doc_tenant_status ON fiscal_documents (tenant_slug, status)');
        $this->addSql('CREATE INDEX idx_fiscal_doc_series_number ON fiscal_documents (series, number)');
        $this->addSql('CREATE INDEX idx_fiscal_emit_doc ON fiscal_emit_attempts (document_uuid)');
        $this->addSql('CREATE INDEX idx_outbound_email_doc ON outbound_email_logs (document_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_outbound_email_doc ON outbound_email_logs');
        $this->addSql('DROP INDEX idx_fiscal_emit_doc ON fiscal_emit_attempts');
        $this->addSql('DROP INDEX idx_fiscal_doc_series_number ON fiscal_documents');
        $this->addSql('DROP INDEX idx_fiscal_doc_tenant_status ON fiscal_documents');
        $this->addSql('ALTER TABLE fiscal_webhook_events DROP http_status, DROP response_body');
    }
}
