<?php

/**
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
class Gems_Snippets_Respondent_RoundsTabsSnippet extends \MUtil_Snippets_TabSnippetAbstract
{
    /**
     * The tab values
     *
     * @var array key => label
     */
    protected $_tabs = array();

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
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
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Required, can be derived from request or respondent
     *
     * @var array
     */
    protected $organizationId;

    /**
     * Required
     *
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;


    /**
     * Required, can be derived from request or respondent
     *
     * @var array
     */
    protected $respondentId;

    /**
     *
     * @var \Gems_Util
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
            if ($this->respondent) {
                $this->organizationId = $this->respondent->getOrganizationId();
            }
            if (! $this->organizationId) {
                $this->organizationId = $this->request->getParam(\MUtil_Model::REQUEST_ID2);
            }
        }

        if ($this->organizationId && (! $this->respondentId)) {
            if ($this->respondent) {
                $this->respondentId = $this->respondent->getId();
            }
            if (! $this->respondentId) {
                $this->respondentId = $this->util->getDbLookup()->getRespondentId(
                        $this->request->getParam(\MUtil_Model::REQUEST_ID1),
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
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        $sql = "SELECT COALESCE(gto_round_description, '') AS label,
                        SUM(
                            CASE
                            WHEN gto_completion_time IS NOT NULL
                            THEN 1
                            ELSE 0
                            END
                        ) AS completed,
                        SUM(
                            CASE
                            WHEN gto_completion_time IS NULL AND
                                gto_valid_from < CURRENT_TIMESTAMP AND
                                (gto_valid_until > CURRENT_TIMESTAMP OR gto_valid_until IS NULL)
                            THEN 1
                            ELSE 0
                            END
                        ) AS waiting,
                        COUNT(*) AS any
                    FROM gems__tokens INNER JOIN
                        gems__surveys ON gto_id_survey = gsu_id_survey INNER JOIN
                        gems__rounds ON gto_id_round = gro_id_round INNER JOIN
                        gems__respondent2track ON gto_id_respondent_track = gr2t_id_respondent_track INNER JOIN
                        gems__reception_codes AS rcto ON gto_reception_code = rcto.grc_id_reception_code INNER JOIN
                        gems__reception_codes AS rctr ON gr2t_reception_code = rctr.grc_id_reception_code
                    WHERE gto_id_respondent = ? AND
                        gro_active = 1 AND
                        gsu_active = 1 AND
                        rcto.grc_success = 1 AND
                        rctr.grc_success = 1
                    GROUP BY COALESCE(gto_round_description, '')
                    ORDER BY MIN(COALESCE(gto_round_order, 100000)), gto_round_description";

        // \MUtil_Echo::track($this->respondentId);
        $tabLabels = $this->db->fetchAll($sql, $this->respondentId);

        if ($tabLabels) {
            $default = null;
            $filters = array();
            $noOpen  = true;
            $tabs    = array();

            foreach ($tabLabels as $row) {
                $name = '_' . \MUtil_Form::normalizeName($row['label']);
                $label = $row['label'] ? $row['label'] : $this->_('empty');
                if ($row['waiting']) {
                    $label = sprintf($this->_('%s (%d open)'), $label, $row['waiting']);
                } else {
                    $label = $label;
                }
                if (! $row['label']) {
                    $label = \MUtil_Html::create('em', $label);
                }

                $filters[$name] = $row['label'];
                $tabs[$name]    = $label;

                if ($noOpen && ($row['completed'] > 0)) {
                    $default  = $name;
                }
                if ($row['waiting'] > 0) {
                    $default = $name;
                    $noOpen  = false;
                }
            }
            if (null === $default) {
                reset($filters);
                $default = key($filters);
            }

            // Set the model
            $reqFilter = $this->request->getParam($this->getParameterKey());
            if (! isset($filters[$reqFilter])) {
                $reqFilter = $default;
            }

            if ('' === $filters[$reqFilter]) {
                $this->model->setMeta('tab_filter', array("(gto_round_description IS NULL OR gto_round_description = '')"));
            } else {
                $this->model->setMeta('tab_filter', array('gto_round_description' => $filters[$reqFilter]));
            }

            // \MUtil_Echo::track($tabs, $reqFilter, $default, $tabLabels);

            $this->defaultTab = $default;
            $this->_tabs      = $tabs;
        }

        return $this->_tabs && parent::hasHtmlOutput();
    }
}
