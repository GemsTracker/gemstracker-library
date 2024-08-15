<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 *
 * @package    Gems
 * @subpackage Tracker\Model
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class LogFieldDataModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
    ) {
        parent::__construct('gems__log_respondent2track2field', $metaModelLoader, $sqlRunner, $translate, 'gems__log_respondent2track2field');

        $metaModelLoader->setChangeFields($this->metaModel, 'glrtf');
    }
}