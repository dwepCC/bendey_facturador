<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hard cleanup fiscal: elimina send_mode legacy y normaliza datos empresa.
 */
final class Version20260529000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fiscal legacy cleanup: send_mode legacy_backend → sunat_direct';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE empresa SET send_mode = 'sunat_direct' WHERE send_mode = 'legacy_backend' OR send_mode IS NULL OR send_mode = ''");
        $this->addSql("UPDATE empresa SET provider = 'sunat' WHERE send_mode = 'sunat_direct' AND (provider IS NULL OR provider = '' OR provider = 'sunat_direct')");
        $this->addSql("UPDATE empresa SET provider = 'validapse' WHERE send_mode = 'pse' AND (provider IS NULL OR provider = '' OR provider = 'pse')");
    }

    public function down(Schema $schema): void
    {
        // irreversible data cleanup
    }
}
