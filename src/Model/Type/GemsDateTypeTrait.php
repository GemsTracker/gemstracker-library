<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

use Zalt\Base\TranslateableTrait;
use Zalt\Model\Type\AbstractDateType;
use Zalt\Validator\Model\Date\IsDateModelValidator;

/**
 * @package    Gems
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
trait GemsDateTypeTrait
{
    use TranslateableTrait;

    public function checkValue(mixed $value)
    {
        if ($value instanceof \Zend_Db_Expr) {
            return (string) $value;
        }
        return parent::checkValue($value);
    }

    protected function getExtraSettings(): array
    {
        return [
            AbstractDateType::$whenDateEmptyClassKey => 'disabled',
            'validators[isDate]' => IsDateModelValidator::class,
            IsDateModelValidator::$notDateMessageKey => $this->_("'%value%' is not a valid date in the format '%format%'."),
            ];
    }
}