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
        protected TokenRepository $tokenRepository,
    )
    {
        $this->translate = $translator;

        $this->description = $this->_('dd-mm-yyyy hh:mm');
    }
}
