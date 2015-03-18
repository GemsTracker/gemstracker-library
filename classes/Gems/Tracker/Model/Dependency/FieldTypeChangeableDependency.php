<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldTypeChangeableDependency.php $
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Tracker\Model\FieldMaintenanceModel;
use MUtil\Model\Dependency\DependencyAbstract;

/**
 * Class that checks whether changing the field type is allowed.
 *
 * @subpackage Tracker_Model
 * @subpackage FieldTypeChangeDependency
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 18-mrt-2015 13:07:12
 */
class FieldTypeChangeableDependency extends DependencyAbstract
{
    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array('gtf_field_type' => array('elementClass', 'onchange'));

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @param string $dependsOn the model field to depend on
     */
    public function __construct($dependsOn)
    {
        $this->_dependentOn = array($dependsOn);

        parent::__construct();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        // Loaded from tracker and tracker does not always have the request as source value
        if (! $this->request instanceof \Zend_Controller_Request_Abstract) {
            $this->request = \Zend_Controller_Front::getInstance()->getRequest();
        }
    }

    /**
     * Returns the changes that must be made in an array consisting of
     *
     * <code>
     * array(
     *  field1 => array(setting1 => $value1, setting2 => $value2, ...),
     *  field2 => array(setting3 => $value3, setting4 => $value4, ...),
     * </code>
     *
     * By using [] array notation in the setting name you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array name => array(setting => value)
     */
    public function getChanges(array $context, $new)
    {
        $subChange = true;

        if (! $new) {
            $fieldName = reset($this->_dependentOn);

            if (isset($context[$fieldName])) {
                $sql = $this->getSql($context[$fieldName]);
                $fid = $this->request->getParam(\Gems_Model::FIELD_ID);

                if ($sql && $fid) {
                    $subChange = ! $this->db->fetchOne($sql, $fid);
                }
            }
        }

        if ($subChange) {
            return array('gtf_field_type' => array(
                'elementClass' => 'Select',
                'onchange'     => 'this.form.submit();',
                ));
        }
    }

    /**
     * Adapt/extend this function if you need different queries
     * for other types
     *
     * @param string $subId The current sub type of field
     * @return string|boolean An sql statement or false
     */
    protected function getSql($subId)
    {
        if ($subId == FieldMaintenanceModel::FIELDS_NAME) {
            return "SELECT gr2t2f_id_field
                FROM gems__respondent2track2field
                WHERE gr2t2f_id_field = ?";
        }

        if ($subId == FieldMaintenanceModel::APPOINTMENTS_NAME) {
            return "SELECT gr2t2a_id_app_field
                FROM gems__respondent2track2appointment
                WHERE gr2t2a_id_app_field = ?";
        }

        return false;
    }
}
