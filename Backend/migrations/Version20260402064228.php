<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402064228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE grow_system (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_68899878989D9B62 ON grow_system (slug)');
        $this->addSql('CREATE TABLE grow_system_compatibility (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, notes CLOB DEFAULT NULL, plant_id INTEGER NOT NULL, grow_system_id INTEGER NOT NULL, CONSTRAINT FK_5CBDB7951D935652 FOREIGN KEY (plant_id) REFERENCES plant (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5CBDB795115FC726 FOREIGN KEY (grow_system_id) REFERENCES grow_system (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5CBDB7951D935652 ON grow_system_compatibility (plant_id)');
        $this->addSql('CREATE INDEX IDX_5CBDB795115FC726 ON grow_system_compatibility (grow_system_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5CBDB7951D935652115FC726 ON grow_system_compatibility (plant_id, grow_system_id)');
        $this->addSql('CREATE TABLE plant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, canonical_name VARCHAR(255) NOT NULL, scientific_name VARCHAR(255) NOT NULL, authorship VARCHAR(255) DEFAULT NULL, slug VARCHAR(100) NOT NULL, kingdom VARCHAR(100) DEFAULT NULL, phylum VARCHAR(100) DEFAULT NULL, class VARCHAR(100) DEFAULT NULL, "order" VARCHAR(100) DEFAULT NULL, family VARCHAR(100) DEFAULT NULL, genus VARCHAR(100) DEFAULT NULL, species VARCHAR(100) DEFAULT NULL, rank VARCHAR(50) DEFAULT NULL, taxonomic_status VARCHAR(50) DEFAULT NULL, accepted_name_id INTEGER DEFAULT NULL, ipni_id VARCHAR(50) DEFAULT NULL, gbif_key INTEGER DEFAULT NULL, common_names CLOB DEFAULT NULL, quality_grade VARCHAR(20) NOT NULL, ai_prefilled BOOLEAN NOT NULL, community_verified BOOLEAN NOT NULL, completeness_score INTEGER DEFAULT NULL, last_reviewed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, multi_harvest BOOLEAN DEFAULT NULL, has_dormant BOOLEAN DEFAULT NULL, cycle_days_min INTEGER DEFAULT NULL, cycle_days_max INTEGER DEFAULT NULL, yield_potential VARCHAR(20) DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB030D72989D9B62 ON plant (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB030D727C139A37 ON plant (gbif_key)');
        $this->addSql('CREATE TABLE stage_params (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stage VARCHAR(20) NOT NULL, ph_min NUMERIC(4, 2) DEFAULT NULL, ph_max NUMERIC(4, 2) DEFAULT NULL, ec_min NUMERIC(5, 2) DEFAULT NULL, ec_max NUMERIC(5, 2) DEFAULT NULL, tds_min INTEGER DEFAULT NULL, tds_max INTEGER DEFAULT NULL, water_temp_min NUMERIC(4, 1) DEFAULT NULL, water_temp_max NUMERIC(4, 1) DEFAULT NULL, dissolved_oxygen_min NUMERIC(4, 2) DEFAULT NULL, n_ppm INTEGER DEFAULT NULL, p_ppm INTEGER DEFAULT NULL, k_ppm INTEGER DEFAULT NULL, ca_ppm INTEGER DEFAULT NULL, mg_ppm INTEGER DEFAULT NULL, s_ppm INTEGER DEFAULT NULL, air_temp_min NUMERIC(4, 1) DEFAULT NULL, air_temp_max NUMERIC(4, 1) DEFAULT NULL, humidity_min INTEGER DEFAULT NULL, humidity_max INTEGER DEFAULT NULL, vpd_min NUMERIC(4, 2) DEFAULT NULL, vpd_max NUMERIC(4, 2) DEFAULT NULL, ppfd_min INTEGER DEFAULT NULL, ppfd_max INTEGER DEFAULT NULL, dli_min NUMERIC(5, 2) DEFAULT NULL, dli_max NUMERIC(5, 2) DEFAULT NULL, photoperiod_hours INTEGER DEFAULT NULL, ph_survive_min NUMERIC(4, 2) DEFAULT NULL, ph_survive_max NUMERIC(4, 2) DEFAULT NULL, air_temp_survive_min NUMERIC(4, 1) DEFAULT NULL, air_temp_survive_max NUMERIC(4, 1) DEFAULT NULL, ec_survive_min NUMERIC(5, 2) DEFAULT NULL, ec_survive_max NUMERIC(5, 2) DEFAULT NULL, plant_id INTEGER NOT NULL, grow_system_id INTEGER DEFAULT NULL, CONSTRAINT FK_1347AFE81D935652 FOREIGN KEY (plant_id) REFERENCES plant (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1347AFE8115FC726 FOREIGN KEY (grow_system_id) REFERENCES grow_system (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1347AFE81D935652 ON stage_params (plant_id)');
        $this->addSql('CREATE INDEX IDX_1347AFE8115FC726 ON stage_params (grow_system_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1347AFE81D935652C27C9369115FC726 ON stage_params (plant_id, stage, grow_system_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE grow_system');
        $this->addSql('DROP TABLE grow_system_compatibility');
        $this->addSql('DROP TABLE plant');
        $this->addSql('DROP TABLE stage_params');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
