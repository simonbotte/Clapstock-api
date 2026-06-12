<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Clapstock project, participant, catalog item and photo tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(12) NOT NULL, name VARCHAR(120) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_project_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participant (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, device_id VARCHAR(120) NOT NULL, display_name VARCHAR(120) NOT NULL, joined_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D79F6B11A76ED395 (project_id), UNIQUE INDEX uniq_participant_project_device (project_id, device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE catalog_item (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, created_by_device_id VARCHAR(120) NOT NULL, title VARCHAR(160) NOT NULL, description LONGTEXT NOT NULL, buy_price NUMERIC(10, 2) NOT NULL, sold_price NUMERIC(10, 2) NOT NULL, quantity INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6EBC3B41166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_photo (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, storage_key VARCHAR(255) NOT NULL, position INT NOT NULL, content_type VARCHAR(120) NOT NULL, size INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C56CC670126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11A76ED395 FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE catalog_item ADD CONSTRAINT FK_6EBC3B41166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_photo ADD CONSTRAINT FK_C56CC670126F525E FOREIGN KEY (item_id) REFERENCES catalog_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11A76ED395');
        $this->addSql('ALTER TABLE catalog_item DROP FOREIGN KEY FK_6EBC3B41166D1F9C');
        $this->addSql('ALTER TABLE item_photo DROP FOREIGN KEY FK_C56CC670126F525E');
        $this->addSql('DROP TABLE participant');
        $this->addSql('DROP TABLE item_photo');
        $this->addSql('DROP TABLE catalog_item');
        $this->addSql('DROP TABLE project');
    }
}
