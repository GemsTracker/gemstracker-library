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

use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class FieldDataModel extends \MUtil\Model\UnionModel
{
    /**
     *
     * @param string $modelName Hopefully unique model name
     * @param string $modelField The name of the field used to store the sub model
     */
    public function __construct($modelName = 'fields_maintenance', $modelField = 'sub')
    {
        parent::__construct($modelName, $modelField);

        $modelF = new \MUtil\Model\TableModel('gems__respondent2track2field');
        \Gems\Model::setChangeFieldsByPrefix($modelF, 'gr2t2f');
        $this->addUnionModel($modelF, null, FieldMaintenanceModel::FIELDS_NAME);

        $modelA = new \MUtil\Model\TableModel('gems__respondent2track2appointment');
        \Gems\Model::setChangeFieldsByPrefix($modelA, 'gr2t2a');

        $mapBase = $modelA->getItemsOrdered();
        $map     = array_combine($mapBase, str_replace('gr2t2a_', 'gr2t2f_', $mapBase));
        $map['gr2t2a_id_app_field'] = 'gr2t2f_id_field';
        $map['gr2t2a_id_appointment'] = 'gr2t2f_value';

        $this->addUnionModel($modelA, $map, FieldMaintenanceModel::APPOINTMENTS_NAME);
    }

    /**
     * Get the SQL table name of the union sub model that should be used for this row.
     *
     * @param array $row
     * @return string
     */
    public function getFieldName($field, $modelName)
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
     * @return string
     */
    public function getTableName($modelName)
    {
        if (! isset($this->_unionModels[$modelName])) {
            return null;
        }

        $model = $this->_unionModels[$modelName];

        if ($model instanceof \MUtil\Model\TableModel) {
            return $model->getTableName();
        }
    }
}
