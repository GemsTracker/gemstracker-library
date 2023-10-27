<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsStaffPhoneFormatPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update staff phone number format for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000202;
    }

    public function up(): array
    {
        // Remove spaces and dashes
        // Remove brackets
        // Replace 06 with +316
        return [
            'UPDATE gems__staff SET gsf_phone_1=REPLACE(REPLACE(gsf_phone_1,"-","")," ","")',
            'UPDATE gems__staff SET gsf_phone_1=REPLACE(REPLACE(gsf_phone_1,"(",""),")","")',
            'UPDATE gems__staff SET gsf_phone_1=CONCAT("+31",SUBSTR(gsf_phone_1,2)) WHERE gsf_phone_1 LIKE "06%"',
        ];
    }
}
