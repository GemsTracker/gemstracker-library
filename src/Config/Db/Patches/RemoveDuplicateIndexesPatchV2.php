<?php

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class RemoveDuplicateIndexesPatchV2 extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    { }

    public function getDescription(): string|null
    {
        return 'Remove unnecessary duplicate indexes from tables';
    }

    public function getOrder(): int
    {
        return 20251209000000;
    }

    public function up(): array
    {
        $statements = [];
        // gls_name_2 is a duplicate of gls_name
        // Key definitions:
        //   KEY `gls_name_2` (`gls_name`)
        //   UNIQUE KEY `gls_name` (`gls_name`),
        //   Column types:
        //         `gls_name` varchar(64) character set utf8mb4 collate utf8mb4_unicode_ci not null
        // To remove this duplicate index, execute:
        // ALTER TABLE `gems__log_setup` DROP INDEX `gls_name_2`;
        if ($this->databaseInfo->tableHasIndex('gems__log_setup', 'gls_name_2')
            && $this->databaseInfo->tableHasIndex('gems__log_setup', 'gls_name')) {
            $statements[] = 'ALTER TABLE gems__log_setup DROP INDEX gls_name_2';
        }
        // Uniqueness of gpl_level ignored because PRIMARY is a duplicate constraint
        // gpl_level is a duplicate of PRIMARY
        // Key definitions:
        //   UNIQUE KEY `gpl_level` (`gpl_level`)
        //   PRIMARY KEY (`gpl_level`),
        // Column types:
        //         `gpl_level` int unsigned not null
        // To remove this duplicate index, execute:
        // ALTER TABLE `gems__patch_levels` DROP INDEX `gpl_level`;
        if ($this->databaseInfo->tableHasIndex('gems__patch_levels', 'gpl_level')) {
            $statements[] = 'ALTER TABLE gems__patch_levels DROP INDEX gpl_level';
        }
        // grr_id_respondent_staff is a duplicate of grr_id_respondent
        // Key definitions:
        //   KEY `grr_id_respondent_staff` (`grr_id_respondent`,`grr_id_staff`),
        //   KEY `grr_id_respondent` (`grr_id_respondent`,`grr_id_staff`),
        // Column types:
        //         `grr_id_respondent` bigint unsigned not null
        //         `grr_id_staff` bigint unsigned default null
        // To remove this duplicate index, execute:
        // ALTER TABLE `gemstracker_acc`.`gems__respondent_relations` DROP INDEX `grr_id_respondent_staff`;
        if ($this->databaseInfo->tableHasIndex('gems__respondent_relations', 'grr_id_respondent_staff')
            && $this->databaseInfo->tableHasIndex('gems__respondent_relations', 'grr_id_respondent')) {
            $statements[] = 'ALTER TABLE gems__respondent_relations DROP INDEX grr_id_respondent_staff';
        }
        // gtr_track_name_2 is a duplicate of gtr_track_name
        // Key definitions:
        //   KEY `gtr_track_name_2` (`gtr_track_name`),
        //   UNIQUE KEY `gtr_track_name` (`gtr_track_name`),
        // Column types:
        //         `gtr_track_name` varchar(40) character set utf8mb4 collate utf8mb4_unicode_ci not null
        // To remove this duplicate index, execute:
        // ALTER TABLE `gemstracker_acc`.`gems__tracks` DROP INDEX `gtr_track_name_2`;
        if ($this->databaseInfo->tableHasIndex('gems__tracks', 'gtr_track_name_2')
            && $this->databaseInfo->tableHasIndex('gems__tracks', 'gtr_track_name')) {
            $statements[] = 'ALTER TABLE gems__tracks DROP INDEX gtr_track_name_2';
        }
        return $statements;
    }
}
