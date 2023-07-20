<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

use Zalt\Base\TranslateableTrait;
use Zalt\Validator\Model\IsDateModelValidator;

/**
 * @package    Gems
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
trait GemsDateTypeTrait
{
    use TranslateableTrait;

    protected function getExtraSettings(): array
    {
        return [
            'validators[isDate]' => IsDateModelValidator::class,
            IsDateModelValidator::notDateMessage => $this->_("'%value%' is not a valid date in the format '%format%'."),
            ];
    }
}