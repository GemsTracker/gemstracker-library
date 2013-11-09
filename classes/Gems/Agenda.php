<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Afenda.php$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Agenda extends MUtil_Translate_TranslateableAbstract
{
    /**
     *
     * @var Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getActivities($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_activities', array('gaa_id_activity', 'gaa_name'))
                ->order('gaa_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gaa_active = 1')
                    ->where('(
                            gaa_id_organization IS NULL
                        AND
                            gaa_name NOT IN (SELECT gaa_name FROM gems__agenda_activities WHERE gaa_id_organization = ?)
                        ) OR
                            gaa_id_organization = ?', $organizationId);
        }
        // MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('activities'));
        return $results;

    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getHealthcareStaff($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_staff', array('gas_id_staff', 'gas_name'))
                ->order('gas_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gas_active = 1')
                    ->where('gas_id_organization = ?', $organizationId);
        }
        // MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('staff'));
        return $results;

    }

    /**
     * Returns an array with identical key => value pairs containing care provision locations.
     *
     * @param int $irgId Optional to slect for single organization
     * @return array
     */
    public function getLocations($orgId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $orgId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__locations', array('glo_id_location', 'glo_name'))
                ->order('glo_name');

        if ($orgId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('glo_active = 1');
            $select->where('glo_id_organization = ?', $orgId);
        }

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('locations'));
        return $results;
    }


    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getProcedures($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_procedures', array('gapr_id_procedure', 'gapr_name'))
                ->order('gapr_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gapr_active = 1')
                    ->where('(
                            gapr_id_organization IS NULL
                        AND
                            gapr_name NOT IN (SELECT gapr_name FROM gems__agenda_procedures WHERE gapr_id_procedure = ?)
                        ) OR
                            gapr_id_organization = ?', $organizationId);
        }
        // MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('procedures'));
        return $results;
    }

    /**
     * Get the status codes for all active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodes()
    {
        $codes = $this->getStatusCodesActive() +
                $this->getStatusCodesInactive();

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesActive()
    {
        $codes = array(
            'AC' => $this->_('Active appointment'),
            'CO' => $this->_('Completed appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for inactive agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesInactive()
    {
        $codes = array(
            'AB' => $this->_('Aborted appointment'),
            'CA' => $this->_('Cancelled appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status keys for active agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysActive()
    {
        return array_keys($this->getStatusCodesActive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return Zend_Db_Expr
     */
    public function getStatusKeysActiveDbQuoted()
    {
        $codes = array();
        foreach ($this->getStatusCodesActive() as $key => $label) {
            $codes[] = $this->db->quote($key);
        }
        return new Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the status keys for inactive agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysInactive()
    {
        return array_keys($this->getStatusCodesInactive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return Zend_Db_Expr
     */
    public function getStatusKeysInactiveDbQuoted()
    {
        $codes = array();
        foreach ($this->getStatusCodesInactive() as $key => $label) {
            $codes[] = $this->db->quote($key);
        }
        return new Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the type codes for agenda items
     *
     * @return array code => label
     */
    public function getTypeCodes()
    {
        return array(
            'A' => $this->_('Ambulatory'),
            'E' => $this->_('Emergency'),
            'F' => $this->_('Field'),
            'H' => $this->_('Home'),
            'I' => $this->_('Inpatient'),
            'S' => $this->_('Short stay'),
            'V' => $this->_('Virtual'),
        );
    }

    /**
     * Find an activity code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchActivity($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select  = $this->db->select();
            $select->from('gems__agenda_activities', array('gaa_id_activity', 'gaa_match_to', 'gaa_id_organization'));

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                if (null === $row['gaa_id_organization']) {
                    $key = 'null';
                } else {
                    $key = $row['gaa_id_organization'];
                }
                foreach (explode('|', $row['gaa_match_to']) as $match) {
                    $matches[$match][$key] = $row['gaa_id_activity'];
                }
            }
            $this->cache->save($matches, $cacheId, array('activities'));
        }

        if (isset($matches[$name])) {
            if (isset($matches[$name][$organizationId])) {
                return $matches[$name][$organizationId];
            }
            if (isset($matches[$name]['null'])) {
                return $matches[$name]['null'];
            }
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__agenda_activities');
        Gems_Model::setChangeFieldsByPrefix($model, 'gaa');

        $values = array(
            'gaa_name'            => $name,
            'gaa_id_organization' => $organizationId,
            'gaa_match_to'        => $name,
            'gaa_active'          => 1,
        );

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('activity', 'activities'));

        return $result['gaa_id_activity'];
    }


    /**
     * Find a healt care provider for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int gas_id_staff staff id
     */
    public function matchHealthcareStaff($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select     = $this->db->select();
            $select->from('gems__agenda_staff')
                    ->order('gas_name');

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                foreach (explode('|', $row['gas_match_to']) as $match) {
                    $matches[$match][$row['gas_id_organization']] = $row['gas_id_staff'];
                }
            }
            $this->cache->save($matches, $cacheId, array('staff'));
        }

        if (isset($matches[$name])) {
            if ($organizationId) {
                if (isset($matches[$name][$organizationId])) {
                    return $matches[$name][$organizationId];
                }
            } else {
                // Return the first location among the organizations
                return reset($matches[$name]);
            }
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__agenda_staff');
        Gems_Model::setChangeFieldsByPrefix($model, 'gas');

        $values = array(
            'gas_name'            => $name,
            'gas_id_organization' => $organizationId,
            'gas_match_to'        => $name,
            'gas_active'          => 1,
        );

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('staff'));

        return $result['gas_id_staff'];
    }

    /**
     * Find a location for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return array location
     */
    public function matchLocation($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select     = $this->db->select();
            $select->from('gems__locations')
                    ->order('glo_name');

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                foreach (explode('|', $row['glo_match_to']) as $match) {
                    $matches[$match][$row['glo_id_organization']] = $row;
                }
            }
            $this->cache->save($matches, $cacheId, array('locations'));
        }

        if (isset($matches[$name])) {
            if ($organizationId) {
                if (isset($matches[$name][$organizationId])) {
                    return $matches[$name][$organizationId];
                }
            } else {
                // Return the first location among the organizations
                return reset($matches[$name]);
            }
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__locations');
        Gems_Model::setChangeFieldsByPrefix($model, 'glo');

        $values = array(
            'glo_name'            => $name,
            'glo_id_organization' => $organizationId,
            'glo_match_to'        => $name,
            'glo_active'   => 1,
        );

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('location', 'locations'));

        return $result;
    }

    /**
     * Find a procedure code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchProcedure($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select  = $this->db->select();
            $select->from('gems__agenda_procedures', array('gapr_id_procedure', 'gapr_match_to', 'gapr_id_organization'));

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                if (null === $row['gapr_id_organization']) {
                    $key = 'null';
                } else {
                    $key = $row['gapr_id_organization'];
                }
                foreach (explode('|', $row['gapr_match_to']) as $match) {
                    $matches[$match][$key] = $row['gapr_id_procedure'];
                }
            }
            $this->cache->save($matches, $cacheId, array('procedures'));
        }

        if (isset($matches[$name])) {
            if (isset($matches[$name][$organizationId])) {
                return $matches[$name][$organizationId];
            }
            if (isset($matches[$name]['null'])) {
                return $matches[$name]['null'];
            }
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__agenda_procedures');
        Gems_Model::setChangeFieldsByPrefix($model, 'gapr');

        $values = array(
            'gapr_name'            => $name,
            'gapr_id_organization' => $organizationId,
            'gapr_match_to'        => $name,
            'gapr_active'          => 1,
        );

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('procedure', 'procedures'));

        return $result['gapr_id_procedure'];
    }
}
