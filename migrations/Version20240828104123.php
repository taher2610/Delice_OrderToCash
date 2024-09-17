<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240828104123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE archive DROP FOREIGN KEY FK_1A416400611C0C56');
        $this->addSql('DROP INDEX UNIQ_1A416400611C0C56 ON archive');
        $this->addSql('ALTER TABLE archive ADD nom VARCHAR(255) NOT NULL, ADD fileName VARCHAR(255) DEFAULT NULL, ADD filePath VARCHAR(255) DEFAULT NULL, ADD updatedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP dossier_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Archive ADD dossier_id INT DEFAULT NULL, DROP nom, DROP fileName, DROP filePath, DROP updatedAt');
        $this->addSql('ALTER TABLE Archive ADD CONSTRAINT FK_1A416400611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1A416400611C0C56 ON Archive (dossier_id)');
    }
}
