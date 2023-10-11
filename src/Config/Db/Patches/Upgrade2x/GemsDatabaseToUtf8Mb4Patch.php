<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsDatabaseToUtf8Mb4Patch extends PatchAbstract
{
    public function __construct(
        protected array $config,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Set database default character set to utf8mb4 with utf8mb4_unicode_ci';
    }

    public function getOrder(): int
    {
        return 20230101000002;
    }

    public function up(): array
    {
        return [
            'ALTER DATABASE `' . $this->config['db']['database'] . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        ];
    }
}
