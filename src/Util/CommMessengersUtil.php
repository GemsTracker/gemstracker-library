<?php

namespace Gems\Util;

class CommMessengersUtil extends UtilAbstract
{
    public function getAvailableMessengerTypes()
    {
        return [
            'mail' => $this->_('Mail'),
            'sms' => $this->_('SMS'),
        ];
    }
}
