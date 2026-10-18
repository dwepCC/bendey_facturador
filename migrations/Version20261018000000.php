<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Evidencia de la verificación manual en SUNAT (panel del facturador).
 * Columnas nulas: MySQL 8 las agrega sin reescribir la tabla.
 */
final class Version20261018000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Verificación manual en SUNAT: verification_json, verified_at, verified_by, verification_source en fiscal_documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fiscal_documents ADD verification_json LONGTEXT DEFAULT NULL, ADD verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD verified_by VARCHAR(180) DEFAULT NULL, ADD verification_source VARCHAR(40) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fiscal_documents DROP verification_json, DROP verified_at, DROP verified_by, DROP verification_source');
    }
}
