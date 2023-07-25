<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @package    Gems
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class GemsDateType extends \Zalt\Model\Type\DateType
{
    use GemsDateTypeTrait;

    public function __construct(TranslatorInterface $translate)
    {
        $this->translate = $translate;

        $this->description = $this->_('dd-mm-yyyy');
    }
}