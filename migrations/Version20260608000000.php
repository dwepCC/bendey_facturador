<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Credenciales OAuth GRE por empresa (REST Greenter\Api).
 */
final class Version20260608000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'GRE OAuth: gre_client_id, gre_client_secret, gre_oauth_configured_at en empresa';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empresa ADD gre_client_id VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD gre_client_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE empresa ADD gre_oauth_configured_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE empresa DROP gre_client_id');
        $this->addSql('ALTER TABLE empresa DROP gre_client_secret');
        $this->addSql('ALTER TABLE empresa DROP gre_oauth_configured_at');
    }
}
