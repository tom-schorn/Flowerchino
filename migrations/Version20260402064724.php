<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402064724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__plant AS SELECT id, canonical_name, scientific_name, authorship, slug, kingdom, phylum, class, "order", family, genus, species, rank, taxonomic_status, accepted_name_id, ipni_id, gbif_key, common_names, quality_grade, ai_prefilled, community_verified, completeness_score, last_reviewed_at, created_at, multi_harvest, has_dormant, cycle_days_min, cycle_days_max, yield_potential FROM plant');
        $this->addSql('DROP TABLE plant');
        $this->addSql('CREATE TABLE plant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, canonical_name VARCHAR(255) NOT NULL, scientific_name VARCHAR(255) NOT NULL, authorship VARCHAR(255) DEFAULT NULL, slug VARCHAR(100) NOT NULL, kingdom VARCHAR(100) DEFAULT NULL, phylum VARCHAR(100) DEFAULT NULL, class VARCHAR(100) DEFAULT NULL, taxon_order VARCHAR(100) DEFAULT NULL, family VARCHAR(100) DEFAULT NULL, genus VARCHAR(100) DEFAULT NULL, species VARCHAR(100) DEFAULT NULL, rank VARCHAR(50) DEFAULT NULL, taxonomic_status VARCHAR(50) DEFAULT NULL, accepted_name_id INTEGER DEFAULT NULL, ipni_id VARCHAR(50) DEFAULT NULL, gbif_key INTEGER DEFAULT NULL, common_names CLOB DEFAULT NULL, quality_grade VARCHAR(20) NOT NULL, ai_prefilled BOOLEAN NOT NULL, community_verified BOOLEAN NOT NULL, completeness_score INTEGER DEFAULT NULL, last_reviewed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, multi_harvest BOOLEAN DEFAULT NULL, has_dormant BOOLEAN DEFAULT NULL, cycle_days_min INTEGER DEFAULT NULL, cycle_days_max INTEGER DEFAULT NULL, yield_potential VARCHAR(20) DEFAULT NULL)');
        $this->addSql('INSERT INTO plant (id, canonical_name, scientific_name, authorship, slug, kingdom, phylum, class, taxon_order, family, genus, species, rank, taxonomic_status, accepted_name_id, ipni_id, gbif_key, common_names, quality_grade, ai_prefilled, community_verified, completeness_score, last_reviewed_at, created_at, multi_harvest, has_dormant, cycle_days_min, cycle_days_max, yield_potential) SELECT id, canonical_name, scientific_name, authorship, slug, kingdom, phylum, class, "order", family, genus, species, rank, taxonomic_status, accepted_name_id, ipni_id, gbif_key, common_names, quality_grade, ai_prefilled, community_verified, completeness_score, last_reviewed_at, created_at, multi_harvest, has_dormant, cycle_days_min, cycle_days_max, yield_potential FROM __temp__plant');
        $this->addSql('DROP TABLE __temp__plant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB030D727C139A37 ON plant (gbif_key)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB030D72989D9B62 ON plant (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__plant AS SELECT id, canonical_name, scientific_name, authorship, slug, kingdom, phylum, class, taxon_order, family, genus, species, rank, taxonomic_status, accepted_name_id, ipni_id, gbif_key, common_names, quality_grade, ai_prefilled, community_verified, completeness_score, last_reviewed_at, created_at, multi_harvest, has_dormant, cycle_days_min, cycle_days_max, yield_potential FROM plant');
        $this->addSql('DROP TABLE plant');
        $this->addSql('CREATE TABLE plant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, canonical_name VARCHAR(255) NOT NULL, scientific_name VARCHAR(255) NOT NULL, authorship VARCHAR(255) DEFAULT NULL, slug VARCHAR(100) NOT NULL, kingdom VARCHAR(100) DEFAULT NULL, phylum VARCHAR(100) DEFAULT NULL, class VARCHAR(100) DEFAULT NULL, "order" VARCHAR(100) DEFAULT NULL, family VARCHAR(100) DEFAULT NULL, genus VARCHAR(100) DEFAULT NULL, species VARCHAR(100) DEFAULT NULL, rank VARCHAR(50) DEFAULT NULL, taxonomic_status VARCHAR(50) DEFAULT NULL, accepted_name_id INTEGER DEFAULT NULL, ipni_id VARCHAR(50) DEFAULT NULL, gbif_key INTEGER DEFAULT NULL, common_names CLOB DEFAULT NULL, quality_grade VARCHAR(20) NOT NULL, ai_prefilled BOOLEAN NOT NULL, community_verified BOOLEAN NOT NULL, completeness_score INTEGER DEFAULT NULL, last_reviewed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, multi_harvest BOOLEAN DEFAULT NULL, has_dormant BOOLEAN DEFAULT NULL, cycle_days_min INTEGER DEFAULT NULL, cycle_days_max INTEGER DEFAULT NULL, yield_potential VARCHAR(20) DEFAULT NULL)');
        $this->addSql('INSERT INTO plant (id, canonical_name, scientific_name, authorship, slug, kingdom, phylum, class, "order", family, genus, species, rank, taxonomic_status, accepted_name_id, ipni_id, gbif_key, common_names, quality_grade, ai_prefilled, community_verified, completeness_score, last_reviewed_at, created_at, multi_harvest, has_dormant, cycle_days_min, cycle_days_max, yield_potential) SELECT id, canonical_name, scientific_name, authorship, slug, kingdom, phylum, class, taxon_order, family, genus, species, rank, taxonomic_status, accepted_name_id, ipni_id, gbif_key, common_names, quality_grade, ai_prefilled, community_verified, completeness_score, last_reviewed_at, created_at, multi_harvest, has_dormant, cycle_days_min, cycle_days_max, yield_potential FROM __temp__plant');
        $this->addSql('DROP TABLE __temp__plant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB030D72989D9B62 ON plant (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB030D727C139A37 ON plant (gbif_key)');
    }
}
