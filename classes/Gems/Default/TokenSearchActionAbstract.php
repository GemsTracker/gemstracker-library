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
 * @version    $Id: TokenSearchActionAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 22-apr-2015 17:53:02
 */
abstract class Gems_Default_TokenSearchActionAbstract extends \Gems_Controller_ModelSnippetActionAbstract
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
        'multiTracks'  => 'getMultiTracks',
        'surveyReturn' => 'setSurveyReturn',
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Token\\PlanTokenSnippet';

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
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        // \MUtil_Model::$verbose = true;
        $model = $this->loader->getTracker()->getTokenModel();
        $model->setCreate(false);

        $model->set('gr2o_patient_nr',       'label', $this->_('Respondent'));
        $model->set('gto_round_description', 'label', $this->_('Round / Details'));
        $model->set('gto_valid_from',        'label', $this->_('Valid from'));
        $model->set('gto_valid_until',       'label', $this->_('Valid until'));
        $model->set('gto_mail_sent_date',    'label', $this->_('Contact date'));
        $model->set('respondent_name',       'label', $this->_('Name'));

        return $model;
    }

    /**
     * Is multi tracks enabled in this project
     *
     * @return boolean
     */
    public function getMultiTracks()
    {
        return $this->escort instanceof \Gems_Project_Tracks_MultiTracksInterface;
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults()
    {
        if (! $this->defaultSearchData) {
            $inFormat = \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');
            $now      = new \MUtil_Date();

            $this->defaultSearchData = array(
                'datefrom'            => $now->toString($inFormat),
                'dateused'            => '_gto_valid_from gto_valid_until',
                'dateuntil'           => $now->toString($inFormat),
                'main_filter'         => 'all',
            );
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @return array or false
     */
    public function getSearchFilter()
    {
        $filter = parent::getSearchFilter();

        if (isset($filter[\Gems_Snippets_AutosearchFormSnippet::PERIOD_DATE_USED])) {
            $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter(
                $filter,
                $this->db,
                \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat'),
                'yyyy-MM-dd HH:mm:ss');

            if ($where) {
                $filter[] = $where;
            }

            unset($filter[\Gems_Snippets_AutosearchFormSnippet::PERIOD_DATE_USED],
                    $filter['datefrom'], $filter['dateuntil']);
        }

        if (! isset($filter['gto_id_organization'])) {
            $filter['gto_id_organization'] = $this->loader->getCurrentUser()->getRespondentOrgFilter();
        }
        $filter['gtr_active']  = 1;
        $filter['gsu_active']  = 1;
        $filter['grc_success'] = 1;

        if (isset($filter['main_filter'])) {
            switch ($filter['main_filter']) {
                case 'answered':
                    $filter[] = 'gto_completion_time IS NOT NULL';
                    break;

                case 'hasnomail':
                    $filter[] = sprintf(
                            "(grs_email IS NULL OR grs_email = '' OR grs_email NOT RLIKE '%s') AND
                                ggp_respondent_members = 1",
                            str_replace('\'', '\\\'', trim(\MUtil_Validate_SimpleEmail::EMAIL_REGEX, '/'))
                            );
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'missed':
                    $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                    $filter[] = 'gto_valid_until < CURRENT_TIMESTAMP';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'notmailed':
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'open':
                    $filter['gto_completion_time'] = null;
                    $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                    $filter[] = '(gto_valid_until >= CURRENT_TIMESTAMP OR gto_valid_until IS NULL)';
                    break;

                // case 'other':
                //    $filter[] = "grs_email IS NULL OR grs_email = '' OR ggp_respondent_members = 0";
                //    $filter['can_email'] = 0;
                //    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                //    break;

                case 'removed':
                    $filter['grc_success'] = 0;
                    break;

                case 'toanswer':
                    $filter[] = 'gto_completion_time IS NULL';
                    break;

                case 'tomail':
                    $filter[] = sprintf(
                            "grs_email IS NOT NULL AND
                                grs_email != '' AND
                                grs_email RLIKE '%s' AND
                                ggp_respondent_members = 1",
                            str_replace('\'', '\\\'', trim(\MUtil_Validate_SimpleEmail::EMAIL_REGEX, '/'))
                            );
                    //$filter[] = "grs_email IS NOT NULL AND grs_email != '' AND ggp_respondent_members = 1";
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'toremind':
                    // $filter['can_email'] = 1;
                    $filter[] = 'gto_mail_sent_date < CURRENT_TIMESTAMP';
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                default:
                    break;
            }
            unset($filter['main_filter']);
        }

        return $filter;
    }

    /**
     * Make we return to this screen after completion
     *
     * @return void
     */
    public function setSurveyReturn()
    {
        $this->loader->getCurrentUser()->setSurveyReturn($this->getRequest());
    }
}
