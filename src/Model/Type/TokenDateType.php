<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

use Gems\Repository\TokenRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\Type\OverwritingTypeInterface;

/**
 * @package    Gems
 * @subpackage Tracker\Model\Type
 * @since      Class available since version 1.0
 */
class TokenDateType extends GemsDateTimeType implements OverwritingTypeInterface
{
    // public string $dateFormat = 'd-m-Y';

    public function __construct(
        TranslatorInterface $translator,
        protected TokenRepository $tokenRepository,
    )
    {
        parent::__construct($translator);
        //if ($this->tokenRepository->)
    }
}