<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Fake
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Fake;

use Gems\Translate\CachedDbTranslationRepository;

/**
 * @package    Gems
 * @subpackage Fake
 * @since      Class available since version 1.0
 */
class FakeCachedDbTranslationRepository extends CachedDbTranslationRepository
{
    public function __construct()
    { }

    public function getTranslations(string $language): array
    {
        return [];
    }
    public function translateRow(string $tableName, string|array $keyFields, array $row, string|null $language = null): array
    {
        return $row;
    }

    public function translateTable(string $cacheKey, string $tableName, string $keyField, array $data): array
    {
        return $data;
    }

    public function translateTables(string $cacheKey, array $tableNames, array $data): array
    {
        return $data;
    }
}