<?php

namespace Gems\User\Validate;

use Gems\Validator\AbstractTranslatingValidator;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberValidator extends AbstractTranslatingValidator
{
    private ?bool $valid = null;

    public function __construct(
        private readonly array $config,
        $options = null,
    ) {
        parent::__construct($options);
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public function isValid($value)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $parsedPhone = $phoneUtil->parse($value, $this->config['account']['edit-auth']['defaultRegion']);
            $this->valid = $phoneUtil->isValidNumber($parsedPhone);
        } catch (NumberParseException) {
            $this->valid = false;
        }

        return $this->valid;
    }

    public function getMessages()
    {
        if ($this->valid) {
            return [];
        } elseif ($this->valid === false) {
            return [
                'invalid' => $this->_('Please provide a valid telephone number'),
            ];
        } else {
            throw new \Exception();
        }
    }
}
