<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store original photo dimensions and 600px thumbnails.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_photo ADD thumbnail_storage_key VARCHAR(255) NOT NULL DEFAULT \'\', ADD thumbnail_content_type VARCHAR(120) NOT NULL DEFAULT \'image/jpeg\', ADD thumbnail_size INT NOT NULL DEFAULT 0, ADD width INT NOT NULL DEFAULT 0, ADD height INT NOT NULL DEFAULT 0, ADD thumbnail_width INT NOT NULL DEFAULT 0, ADD thumbnail_height INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_photo DROP thumbnail_storage_key, DROP thumbnail_content_type, DROP thumbnail_size, DROP width, DROP height, DROP thumbnail_width, DROP thumbnail_height');
    }
}
