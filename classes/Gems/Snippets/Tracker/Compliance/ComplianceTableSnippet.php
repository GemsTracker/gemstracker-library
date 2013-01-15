<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ComplianceTableSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_Tracker_Compliance_ComplianceTableSnippet extends Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::createModel();
        $trackId = $this->getTrackId();

        if (! $trackId) {
            return $model;
        }

        $select = $this->db->select();
        $select->from('gems__rounds', array('gro_id_round', 'gro_id_order', 'gro_round_description'))
                ->where('gro_id_track = ?', $trackId)
                ->order('gro_id_order');

        $data = $this->db->fetchAll($select);

        if (! $data) {
            return $model;
        }

        $status = new Zend_Db_Expr("
            CASE
            WHEN gto_completion_time IS NOT NULL     THEN 'A'
            WHEN gto_valid_from IS NULL              THEN 'U'
            WHEN gto_valid_from > CURRENT_TIMESTAMP  THEN 'W'
            WHEN gto_valid_until < CURRENT_TIMESTAMP THEN 'M'
            ELSE 'O'
            END
            ");

        // $labels = $model->getCol('label');

        $select = $this->db->select();
        $select->from('gems__tokens', array('gto_id_respondent_track', 'gto_id_round', 'status' => $status))
                ->joinInner('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array())
                ->where('grc_success = 1')
                ->where('gto_id_track = ?', $trackId)
                ->order('gto_id_respondent_track')
                ->order('gto_round_order');

        // MUtil_Echo::track($this->db->fetchAll($select));

        $newModel = new MUtil_Model_SelectModel($select, 'tok');
        $newModel->setKeys(array('gto_id_respondent_track'));
        // $model->addLeftTable('gems__tokens', array('gr2t_id_track' => 'gto_id_track'));
        // $model->addLeftTable('gems__reception_codes', array('gto_reception_code' => 'grc_id_reception_code'));
        // $model->addFilter(array('grc_success' => 1));
        // $newModel = $model;

        $transformer = new MUtil_Model_Transform_CrossTabTransformer();
        $transformer->setCrosstabFields('gto_id_round', 'status');

        foreach ($data as $row) {
            $name = 'col_' . $row['gro_id_round'];
            $transformer->set($name, 'label', $row['gro_id_order'], 'description', $row['gro_round_description']);
            // break;
        }

        $newModel->addTransformer($transformer);
        // MUtil_Echo::track($data);

        $joinTrans = new MUtil_Model_Transform_JoinTransformer();
        $joinTrans->addModel($newModel, array('gr2t_id_respondent_track' => 'gto_id_respondent_track'));

        $model->resetOrder();
        $model->set('gr2o_patient_nr');
        $model->set('gr2t_start_date');
        $model->addTransformer($joinTrans);
        return $model;

        return $newModel;
    }

    /**
     * Returns a show menu item, if access is allowed by privileges
     *
     * @return Gems_Menu_SubMenuItem
     */
    protected function getShowMenuItem()
    {
        return $this->findMenuItem('track', 'show-track');
    }

    /**
     *
     * @return int Return the track id if any or null
     */
    public function getTrackId()
    {
        if ($this->requestCache) {
            $data = $this->requestCache->getProgramParams();
            if (isset($data['gr2t_id_track'])) {
                return $data['gr2t_id_track'];
            }
        } else {
            return $this->request->getParam('gr2t_id_track');
        }
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        $trackId = $this->getTrackId();

        if ($trackId) {
            parent::processFilterAndSort($model);
        } else {
            $model->setFilter(array('1=0'));
            $this->onEmpty = $this->_('No track selected...');
        }
    }
}
