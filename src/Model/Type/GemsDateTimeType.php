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
class GemsDateTimeType extends \Zalt\Model\Type\DateTimeType
{
    use GemsDateTypeTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translate = $translator;

        $this->description = $this->_('dd-mm-yyyy hh:mm');
    }
}