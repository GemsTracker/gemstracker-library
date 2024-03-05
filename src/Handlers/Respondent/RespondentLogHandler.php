<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Handlers\LogHandler;
use Gems\Model;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\SnippetsActions\SnippetActionInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:36:20
 */
class RespondentLogHandler extends LogHandler
{
    public static array $parameterMaps = [
        MetaModelInterface::REQUEST_ID1 => 'gr2o_patient_nr',
        MetaModelInterface::REQUEST_ID2 => 'gr2o_id_organization',
    ];

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        static $initialized = false;
        parent::getModel($action);
        if (!$initialized) {
            $this->logModel->addTable(
                'gems__respondent2org',
                ['gla_respondent_id' => 'gr2o_id_user', 'gla_organization' => 'gr2o_id_organization']
            );
            $this->logModel->getMetaModel()->setKeys([Model::LOG_ITEM_ID => 'gla_id']);
            foreach(static::$parameterMaps as $attributeName => $fieldName) {
                $this->logModel->getMetaModel()->addMap($attributeName, $fieldName);
            }
            $initialized = true;
        }

        return $this->logModel;
    }
}
