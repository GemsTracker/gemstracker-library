<?php

declare(strict_types=1);

/**
 * @package    GemsTest
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace GemsTest\Repository;

use MUtil\Translate\Translator;

/**
 * @package    GemsTest
 * @subpackage Repository
 * @since      Class available since version 1.0
 */
class MockTokenRepository extends \Gems\Repository\TokenRepository
{
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

}