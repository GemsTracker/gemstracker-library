<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

use Zalt\Base\TranslatorInterface;
use Zalt\Model\Type\TimeType;

/**
 * @package    Gems
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class GemsTimeType extends TimeType
{
    use GemsDateTypeTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translate = $translator;

        $this->description = $this->_('hh:mm');
    }
}