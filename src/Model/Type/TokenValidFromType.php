<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

use Gems\Repository\TokenRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Type\MaybeTimeType;
use Zalt\Model\Type\OverwritingTypeInterface;

/**
 * @package    Gems
 * @subpackage Tracker\Model\Type
 * @since      Class available since version 1.0
 */
class TokenValidFromType extends MaybeTimeType implements OverwritingTypeInterface
{
    use GemsDateTypeTrait;

    public function __construct(
        TranslatorInterface $translator,
    )
    {
        $this->translate = $translator;

        $this->description = $this->_('dd-mm-yyyy hh:mm');
    }

    public function setMaybeTimeValue(string $value): void
    {
        $this->maybeTimeValue = $value;
    }

    public function useTime(bool $useTime = true)
    {
        if ($useTime) {
            $this->dateFormat  = 'd-m-Y H:i';
            $this->description = $this->_('dd-mm-yyyy hh:mm');
            $this->editTime = true;
        } else {
            $this->dateFormat  = 'd-m-Y';
            $this->description = $this->_('dd-mm-yyyy');
            $this->editTime = false;
        }
    }
}
