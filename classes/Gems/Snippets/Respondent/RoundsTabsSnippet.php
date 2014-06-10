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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Snippets_Respondent_RoundsTabsSnippet extends MUtil_Snippets_TabSnippetAbstract
{
    /**
     * The tab values
     *
     * @var array key => label
     */
    protected $_tabs = array();

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Default href parameter values
     *
     * Clicking a tab always resets the page counter
     *
     * @var array
     */
    protected $href = array('page' => null);

    /**
     * The RESPONDENT model, not the token model
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * The fields in respondentData that can contain an organization id.
     *
     * @var array
     */
    protected $organizationFields = array(
        'gr2o_id_organization',
        'gr2t_id_organization',
        'gto_id_organization',
        'gor_id_organization',
        );

    /**
     * Required, can be derived from request or respondentData
     *
     * @var array
     */
    protected $organizationId;

    /**
     * Required
     *
     * @var array
     */
    protected $respondentData;


    /**
     * The fields in respondentData that can contain a respondent id.
     *
     * @var array
     */
    protected $respondentFields = array(
        'grs_id_user',
        'gr2o_id_user',
        'gr2t_id_user',
        'gto_id_respondent',
        );

    /**
     * Required, can be derived from request or respondentData
     *
     * @var array
     */
    protected $respondentId;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {

        if (! $this->organizationId) {
            if (isset($this->respondentData)) {
                foreach ($this->organizationFields as $field) {
                    if (isset($this->respondentData[$field]) && $this->respondentData[$field]) {
                        $this->organizationId = $this->respondentData[$field];
                        break;
                    }
                }
            }
            if (! $this->organizationId) {
                $this->organizationId = $this->request->getParam(MUtil_Model::REQUEST_ID2);
            }
        }

        if ($this->organizationId && (! $this->respondentId)) {
            if (isset($this->respondentData)) {
                foreach ($this->respondentFields as $field) {
                    if (isset($this->respondentData[$field]) && $this->respondentData[$field]) {
                        $this->respondentId = $this->respondentData[$field];
                        break;
                    }
                }
            }
            if (! $this->respondentId) {
                $this->respondentId = $this->util->getDbLookup()->getRespondentId(
                        $this->request->getParam(MUtil_Model::REQUEST_ID1),
                        $this->organizationId
                        );
            }
        }

        return $this->organizationId && $this->respondentId && $this->db && $this->model;
    }

    /**
     * Return optionally the single parameter key which should left out for the default value,
     * but is added for all other tabs.
     *
     * @return mixed
     */
    protected function getParameterKey()
    {
        // gro_round_description
        return 'round';
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs()
    {
        return $this->_tabs;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        $sql = "SELECT COALESCE(gto_round_description, '') AS label,
                        MAX(
                            CASE
                            WHEN gto_valid_from IS NULL OR
                                gto_valid_from > CURRENT_TIMESTAMP OR
                                gto_valid_until < CURRENT_TIMESTAMP
                            THEN 0
                            WHEN gto_completion_time IS NOT NULL
                            THEN 1
                            ELSE 2
                            END
                        ) AS status
                    FROM gems__tokens INNER JOIN
                        gems__reception_codes ON gto_reception_code = grc_id_reception_code
                    WHERE gto_id_respondent = ? AND
                        gto_id_organization = ? AND
                        grc_success = 1
                    GROUP BY COALESCE(gto_round_description, '')
                    ORDER BY MIN(COALESCE(gto_round_order, 100000)), gto_round_description, gto_id_track";

        $tabLabels = $this->db->fetchPairs($sql, array($this->respondentId, $this->organizationId));

        if ($tabLabels) {
            $default  = null;
            $tabState = -1;
            $tabs     = array();

            foreach ($tabLabels as $label => $state) {
                // $name = '_' . MUtil_Form::normalizeName($label);
                $name = $label;

                if (strlen($label)) {
                    $tabs[$name] = $label;
                } else {
                    $tabs[$name] = MUtil_Html::raw($this->_('&laquo;empty&raquo;'));
                }

                if ($state > $tabState) {
                    $default  = $name;
                    $tabState = $state;
                }
            }

            // Set the model
            $reqFilter = $this->request->getParam($this->getParameterKey());
            if (! isset($tabs[$reqFilter])) {
                $reqFilter = $default;
            }
            $this->model->setMeta('tab_filter', array('gto_round_description' => $reqFilter));

            // MUtil_Echo::track($tabs, $reqFilter, $default, $tabLabels);

            $this->defaultTab = $default;
            $this->_tabs      = $tabs;
        }

        return $this->_tabs && parent::hasHtmlOutput();
    }
}
