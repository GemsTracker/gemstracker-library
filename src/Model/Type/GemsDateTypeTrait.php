<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

use DateTimeInterface;
use Carbon\Carbon;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslateableTrait;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\AbstractDateType;
use Zalt\Validator\Model\Date\IsDateModelValidator;
use Zend_Db_Expr;

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
        if ($value instanceof Expression) {
            return $value->getExpression();
        }
        if ($value instanceof Zend_Db_Expr) {
            return (string) $value;
        }
        return parent::checkValue($value);
    }

    public function format($value, string $name, MetaModelInterface $metaModel)
    {
        if (! $value instanceof DateTimeInterface) {
            $value = self::toDate(
                $value,
                $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                false);
        }
        if ($value instanceof DateTimeInterface) {
            $carbon = Carbon::instance($value)->locale($this->translate->getLocale());
            return $carbon->translatedFormat($metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat));
        }
        if (! $value) {
            return $this->getNullDisplayValue($name, $metaModel);
        }

        return $value;
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