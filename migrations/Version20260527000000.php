<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Índice cursor pagination + tenant_id/status para panel SaaS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_fiscal_doc_cursor ON fiscal_documents (created_at, id)');
        $this->addSql('CREATE INDEX idx_fiscal_doc_tenant_id ON fiscal_documents (tenant_id, status)');
        $this->addSql('CREATE INDEX idx_fiscal_doc_tenant_slug_status ON fiscal_documents (tenant_slug, status, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_fiscal_doc_tenant_slug_status ON fiscal_documents');
        $this->addSql('DROP INDEX idx_fiscal_doc_tenant_id ON fiscal_documents');
        $this->addSql('DROP INDEX idx_fiscal_doc_cursor ON fiscal_documents');
    }
}
