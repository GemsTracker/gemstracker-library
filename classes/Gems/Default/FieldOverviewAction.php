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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldOverviewAction.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 9-mrt-2015 17:15:29
 */
class Gems_Default_FieldOverviewAction extends \Gems_Controller_ModelSnippetActionAbstract
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
    protected $indexStartSnippets = array(
        'Generic_ContentTitleSnippet',
        'Tracker_Compliance_ComplianceSearchFormSnippet'
        );

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

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
        //
        $model = new \Gems_Model_JoinModel('resptrack' , 'gems__respondent2track');
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

        $filter = $this->getSearchFilter();
        if (! (isset($filter['gr2t_id_organization']) && $filter['gr2t_id_organization'])) {
            $model->addFilter(array('gr2t_id_organization' => $this->loader->getCurrentUser()->getRespondentOrgFilter()));
        }
        if (! (isset($filter['gr2t_id_track']) && $filter['gr2t_id_track'])) {
            $model->setFilter(array('1=0'));
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
            return $model;
        }

        // Add the period filter - if any
        if ($where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db)) {
            $model->addFilter(array($where));
        }

        $trackId = $filter['gr2t_id_track'];
        $engine = $this->loader->getTracker()->getTrackEngine($trackId);
        $engine->addFieldsToModel($model, false);

        return $model;
    }

    /**
     * Outputs the model to excel, applying all filters and searches needed
     *
     * When you want to change the output, there are two places to check:
     *
     * 1. $this->addExcelColumns($model), where the model can be changed to have labels for columns you
     * need exported
     *
     * 2. $this->getExcelData($data, $model) where the supplied data and model are merged to get output
     * (by default all fields from the model that have a label)
     */
    public function excelAction()
    {
        $model = $this->getModel();
        $model->set('gr2t_id_respondent_track', 'label', 'attribute_4', 'order', '5');

        foreach ($model->getColNames('has_orig') as $name) {
            $model->set($name . '_orig', 'label', $model->get($name, 'label') . ' {RAW]');
        }

        return parent::excelAction();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Respondent Track fields');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track field', 'track fields', $count);
    }
}
