<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Repository;

/**
 * @package    Gems
 * @subpackage Repository
 * @since      Class available since version 1.0
 */
class ImportRepository
{
    protected readonly array $importData;

    public function __construct(array $config)
    {
        $this->importData = $config['import'];
    }

    public function getImportFailureDir(): string
    {
        return $this->importData['dirs']['failed'] ?? 'data/uploads';
    }

    public function getImportSuccessDir(): string
    {
        return $this->importData['dirs']['success'] ?? 'data/uploads';
    }

    public function getImportTempDir(): string
    {
        return $this->importData['dirs']['temp'] ?? 'data/uploads';
    }
}