<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla admin_user para login del dashboard fiscal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_user (
            id INT AUTO_INCREMENT NOT NULL,
            username VARCHAR(64) NOT NULL,
            password VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            must_change_password TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_AD8A54A9F85E0677 (username),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE admin_user');
    }
}
