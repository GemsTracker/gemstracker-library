<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\TranslateableTrait;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class GemsJoinModel extends \Zalt\Model\Sql\JoinModel
{
    use TranslateableTrait;

    public function __construct(MetaModelInterface $metaModel, SqlRunnerInterface $sqlRunner, TranslatorInterface $translate)
    {
        parent::__construct($metaModel, $sqlRunner);

        $this->translate = $translate;
    }

}