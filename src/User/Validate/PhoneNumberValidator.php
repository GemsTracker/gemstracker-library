<?php

namespace Gems\User\Validate;

use Gems\Validator\AbstractTranslatingValidator;
use Laminas\Validator\AbstractValidator;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;

class PhoneNumberValidator extends AbstractValidator
{
    use TranslateableTrait;

    private ?bool $valid = null;

    public function __construct(
        private readonly array $config,
        TranslatorInterface $translator,
        $options = null,
    ) {
        parent::__construct($options);

        $this->translate = $translator;
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (! $value) {
            // Use different validator for required values
            return true;
        }
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
