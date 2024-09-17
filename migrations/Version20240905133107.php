<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240905133107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mail DROP FOREIGN KEY FK_F1140376611C0C56');
        $this->addSql('DROP INDEX UNIQ_F1140376611C0C56 ON mail');
        $this->addSql('ALTER TABLE mail ADD dest_principal VARCHAR(255) NOT NULL, ADD dest_copie VARCHAR(255) NOT NULL, DROP dossier_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Mail ADD dossier_id INT DEFAULT NULL, DROP dest_principal, DROP dest_copie');
        $this->addSql('ALTER TABLE Mail ADD CONSTRAINT FK_F1140376611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1140376611C0C56 ON Mail (dossier_id)');
    }
}
