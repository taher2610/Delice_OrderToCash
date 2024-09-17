<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240904123904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mail ADD dossier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mail ADD CONSTRAINT FK_F1140376611C0C56 FOREIGN KEY (dossier_id) REFERENCES Dossier (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1140376611C0C56 ON mail (dossier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Mail DROP FOREIGN KEY FK_F1140376611C0C56');
        $this->addSql('DROP INDEX UNIQ_F1140376611C0C56 ON Mail');
        $this->addSql('ALTER TABLE Mail DROP dossier_id');
    }
}
