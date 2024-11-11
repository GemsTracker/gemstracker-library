<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use Gems\Model\MetaModelLoader;
use Gems\Model\UnionModel;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlTableModel;

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class FieldDataModel extends UnionModel
{
    /**
     *
     * @param string $modelName Hopefully unique model name
     * @param string $modelField The name of the field used to store the sub model
     */
    public function __construct(
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        string $modelName = 'fields_maintenance',
        string $modelField = 'sub')
    {
        parent::__construct($metaModelLoader, $translate, $modelName, $modelField);

        $modelF = $metaModelLoader->createTableModel('gems__respondent2track2field');
        $metaModelLoader->setChangeFields($modelF->getMetaModel(),'gr2t2f');
        $this->addUnionModel($modelF, null, FieldMaintenanceModel::FIELDS_NAME);

        $modelA = $metaModelLoader->createTableModel('gems__respondent2track2appointment');
        $metaModelLoader->setChangeFields($modelA->getMetaModel(),'gr2t2a');

        $mapBase = $modelA->getMetaModel()->getItemsOrdered();
        $map     = array_combine($mapBase, str_replace('gr2t2a_', 'gr2t2f_', $mapBase));
        $map['gr2t2a_id_app_field'] = 'gr2t2f_id_field';
        $map['gr2t2a_id_appointment'] = 'gr2t2f_value';

        $this->addUnionModel($modelA, $map, FieldMaintenanceModel::APPOINTMENTS_NAME);
    }

    /**
     * Get the SQL table name of the union sub model that should be used for this row.
     */
    public function getFieldName(string $field, string $modelName): string
    {
        if (isset($this->_unionMapsTo[$modelName][$field])) {
            return $this->_unionMapsTo[$modelName][$field];
        }

        return $field;
    }

    /**
     * Get the SQL table name of the union sub model that should be used for this row.
     *
     * @param string $modelName Name of the submodel
     * @return string|null
     */
    public function getTableName(string $modelName): string|null
    {
        if (! isset($this->_unionModels[$modelName])) {
            return null;
        }

        $model = $this->getUnionModel($modelName);

        if ($model instanceof SqlTableModel) {
            return $model->getName();
        }
        return null;
    }
}
