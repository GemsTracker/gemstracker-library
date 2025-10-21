<?php

declare(strict_types=1);

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\PatchAbstract;

class LargerCommMessengerIdentifierPatch extends PatchAbstract
{

    public function getDescription(): string|null
    {
        return 'Make the messenger identifier column bigger, so it can hold class names';
    }

    public function getOrder(): int
    {
        return 20251021000000;
    }

    public function up(): array
    {
        return [
            "ALTER TABLE `gems__comm_messengers`CHANGE `gcm_messenger_identifier` `gcm_messenger_identifier` varchar(255) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `gcm_description`;",
        ];
    }

    public function down(): ?array
    {
        return [
            "ALTER TABLE `gems__comm_messengers`CHANGE `gcm_messenger_identifier` `gcm_messenger_identifier` varchar(32) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `gcm_description`;",
        ];
    }
}