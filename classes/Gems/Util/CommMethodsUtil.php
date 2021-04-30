<?php

namespace Gems\Util;

class CommMethodsUtil extends UtilAbstract
{
    public function getAvailableMethodTypes()
    {
        return [
            'mail' => $this->_('Mail'),
            'sms' => $this->_('SMS'),
        ];
    }
}
