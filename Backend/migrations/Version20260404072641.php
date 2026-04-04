<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404072641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename reserved column "rank" to "taxon_rank" (MySQL only)';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->getDatabasePlatform() instanceof SQLitePlatform,
            'SQLite: column already named taxon_rank via schema create'
        );

        $this->addSql('ALTER TABLE plant RENAME COLUMN `rank` TO taxon_rank');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->getDatabasePlatform() instanceof SQLitePlatform,
            'SQLite: skip'
        );

        $this->addSql('ALTER TABLE plant RENAME COLUMN taxon_rank TO `rank`');
    }
}
