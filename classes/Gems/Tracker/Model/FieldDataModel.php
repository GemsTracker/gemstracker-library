<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class FieldDataModel extends \MUtil_Model_UnionModel
{
    /**
     *
     * @param string $modelName Hopefully unique model name
     * @param string $modelField The name of the field used to store the sub model
     */
    public function __construct($modelName = 'fields_maintenance', $modelField = 'sub')
    {
        parent::__construct($modelName, $modelField);

        $modelF = new \MUtil_Model_TableModel('gems__respondent2track2field');
        \Gems_Model::setChangeFieldsByPrefix($modelF, 'gr2t2f');
        $this->addUnionModel($modelF, null, FieldMaintenanceModel::FIELDS_NAME);

        $modelA = new \MUtil_Model_TableModel('gems__respondent2track2appointment');
        \Gems_Model::setChangeFieldsByPrefix($modelA, 'gr2t2a');

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

        if ($model instanceof \MUtil_Model_TableModel) {
            return $model->getTableName();
        }
    }
}
