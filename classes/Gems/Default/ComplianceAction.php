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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ComplianceAction.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Default_ComplianceAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker_Compliance_ComplianceTableSnippet';

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic_ContentTitleSnippet', 'Tracker_Compliance_ComplianceSearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Tracker_TokenStatusLegenda', 'Generic_CurrentButtonRowSnippet');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new Gems_Model_JoinModel('resptrack' , 'gems__respondent2track');
        $model->addTable('gems__respondent2org', array(
            'gr2t_id_user' => 'gr2o_id_user',
            'gr2t_id_organization' => 'gr2o_id_organization'
            ));
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__reception_codes', array('gr2t_reception_code' => 'grc_id_reception_code'));
        $model->addFilter(array('grc_success' => 1));

        $model->resetOrder();
        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));
        $model->set('gr2t_start_date', 'label', $this->_('Start date'), 'dateFormat', 'dd-MM-yyyy');
        $model->set('gr2t_end_date',   'label', $this->_('End date'), 'dateFormat', 'dd-MM-yyyy');

        $filter = $this->util->getRequestCache('index')->getProgramParams();
        if (! (isset($filter['gr2t_id_track']) && $filter['gr2t_id_track'])) {
            $model->setFilter(array('1=0'));
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
            return $model;
        }

        // Add the period filter - if any
        if ($where = Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db)) {
            $model->addFilter(array($where));
        }

        $select = $this->db->select();
        $select->from('gems__rounds', array('gro_id_round', 'gro_id_order', 'gro_round_description'))
                ->joinInner('gems__surveys', 'gro_id_survey = gsu_id_survey', array('gsu_survey_name'))
                ->where('gro_id_track = ?', $filter['gr2t_id_track'])
                ->order('gro_id_order');

        if (isset($filter['gsu_id_primary_group']) && $filter['gsu_id_primary_group']) {
            $select->where('gsu_id_primary_group = ?', $filter['gsu_id_primary_group']);
        }

        $data = $this->db->fetchAll($select);

        if (! $data) {
            return $model;
        }

        $status = new Zend_Db_Expr("
            CASE
            WHEN grc_success = 0                     THEN 'D'
            WHEN gto_completion_time IS NOT NULL     THEN 'A'
            WHEN gto_valid_from IS NULL              THEN 'U'
            WHEN gto_valid_from > CURRENT_TIMESTAMP  THEN 'W'
            WHEN gto_valid_until < CURRENT_TIMESTAMP THEN 'M'
            ELSE 'O'
            END
            ");

        $select = $this->db->select();
        $select->from('gems__tokens', array(
            'gto_id_respondent_track', 'gto_id_round', 'gto_id_token', 'status' => $status,
            ))
                ->joinInner('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array())
                // ->where('grc_success = 1')
                ->where('gto_id_track = ?', $filter['gr2t_id_track'])
                ->order('grc_success')
                ->order('gto_id_respondent_track')
                ->order('gto_round_order');

        // MUtil_Echo::track($this->db->fetchAll($select));
        $newModel = new MUtil_Model_SelectModel($select, 'tok');
        $newModel->setKeys(array('gto_id_respondent_track'));

        $transformer = new MUtil_Model_Transform_CrossTabTransformer();
        $transformer->addCrosstabField('gto_id_round', 'status', 'stat_')
                ->addCrosstabField('gto_id_round', 'gto_id_token', 'tok_');

        foreach ($data as $row) {
            $name = 'stat_' . $row['gro_id_round'];
            $transformer->set($name, 'label', MUtil_Lazy::call('substr', $row['gsu_survey_name'], 0, 2),
                    'description', sprintf("%s\n[%s]", $row['gsu_survey_name'], $row['gro_round_description']),
                    'noSort', true,
                    'round', $row['gro_round_description']
                    );
            $transformer->set('tok_' . $row['gro_id_round']);
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
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Compliance');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }
}
