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
 * @version    $Id$
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
class Gems_Default_SummaryAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'browse'    => false,
        'extraSort' => array('gro_id_order' => SORT_ASC),
    );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker_Summary_SummaryTableSnippet';

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic_ContentTitleSnippet', 'Tracker_Summary_SummarySearchFormSnippet');

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
        $select = $this->getSelect();

        // MUtil_Model::$verbose = true;
        $model = new MUtil_Model_SelectModel($select, 'summary');

        // Make sure of filter and sort for these fields
        $model->set('gro_id_order');
        $model->set('gsu_id_primary_group');
        $model->set('gto_id_track');
        $model->set('gto_id_organization');

        $model->resetOrder();
        $model->set('gro_round_description', 'label', $this->_('Round'));
        $model->set('gsu_survey_name',       'label', $this->_('Survey'));
        $model->set('answered', 'label', $this->_('Answered'), 'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        $model->set('missed',   'label', $this->_('Missed'),   'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        $model->set('open',     'label', $this->_('Open'),     'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        $model->set('total',    'label', $this->_('Total'),    'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('future',   'label', $this->_('Future'),   'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('unknown',  'label', $this->_('Unknown'),  'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('is',       'label', ' ',                  'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('success',  'label', $this->_('Success'),    'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('removed',  'label', $this->_('Removed'),  'tdClass', 'deleted centerAlign',
        //         'thClass', 'centerAlign');

        $model->set('gsu_id_primary_group',  'label', $this->_('Filler'),
                'multiOptions', $this->util->getDbLookup()->getGroups());

        $data = $this->util->getRequestCache('index')->getProgramParams();
        if (! (isset($data['gto_id_organization']) && $data['gto_id_organization'])) {
            $model->addFilter(array('gto_id_organization' => $this->loader->getCurrentUser()->getRespondentOrgFilter()));
        }

        if (isset($data['gto_id_track']) && $data['gto_id_track']) {
            // Add the period filter
            if ($where = Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($data, $this->db)) {
                $select->joinInner('gems__respondent2track', 'gto_id_respondent_track = gr2t_id_respondent_track', array());
                $model->addFilter(array($where));
            }
        } else {
            $model->setFilter(array('1=0'));
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
        }

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Summary');
    }

    /**
     * Select creation function, allowes overruling in child classes
     *
     * @return Zend_Db_Select
     */
    public function getSelect()
    {
        $select = $this->db->select();

        $fields['answered'] = new Zend_Db_Expr("SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NOT NULL
            THEN 1 ELSE 0 END
            )");
        $fields['missed']   = new Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP
            THEN 1 ELSE 0 END
            )');
        $fields['open']   = new Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND
                gto_valid_from <= CURRENT_TIMESTAMP AND
                (gto_valid_until >= CURRENT_TIMESTAMP OR gto_valid_until IS NULL)
            THEN 1 ELSE 0 END
            )');
        $fields['total'] = new Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND (
                    gto_completion_time IS NOT NULL OR
                    (gto_valid_from IS NOT NULL AND gto_valid_from <= CURRENT_TIMESTAMP)
                )
            THEN 1 ELSE 0 END
            )');
        /*
        $fields['future'] = new Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND gto_valid_from > CURRENT_TIMESTAMP
            THEN 1 ELSE 0 END
            )');
        $fields['unknown'] = new Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND gto_valid_from IS NULL
            THEN 1 ELSE 0 END
            )');
        $fields['is']      = new Zend_Db_Expr("'='");
        $fields['success'] = new Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1
            THEN 1 ELSE 0 END
            )');
        $fields['removed'] = new Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 0
            THEN 1 ELSE 0 END
            )');
        // */

        $select = $this->db->select();
        $select->from('gems__tokens', $fields)
                ->joinInner('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array())
                ->joinInner('gems__rounds', 'gto_id_round = gro_id_round',
                        array('gro_round_description', 'gro_id_survey'))
                ->joinInner('gems__surveys', 'gro_id_survey = gsu_id_survey',
                        array('gsu_survey_name', 'gsu_id_primary_group'))
                ->group(array('gro_id_order', 'gro_round_description', 'gsu_survey_name', 'gsu_id_primary_group'));

        return $select;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
    }
}
