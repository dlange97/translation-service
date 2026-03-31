<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add instance_id column to translation and translation_group tables';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('translation') && !$schema->getTable('translation')->hasColumn('instance_id')) {
            $this->addSql('ALTER TABLE translation ADD instance_id VARCHAR(36) DEFAULT NULL');
        }
        if ($schema->hasTable('translation_group') && !$schema->getTable('translation_group')->hasColumn('instance_id')) {
            $this->addSql('ALTER TABLE translation_group ADD instance_id VARCHAR(36) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE translation_group DROP instance_id');
        $this->addSql('ALTER TABLE translation DROP instance_id');
    }
}
