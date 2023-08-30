<?php

declare(strict_types=1);

/**
 * @package    GemsTest
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace GemsTest\Repository;

use Zalt\Base\TranslatorInterface;

/**
 * @package    GemsTest
 * @subpackage Repository
 * @since      Class available since version 1.0
 */
class MockTokenRepository extends \Gems\Repository\TokenRepository
{
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

}