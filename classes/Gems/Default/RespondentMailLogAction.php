<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Controller for looking at mail activity
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_RespondentMailLogAction extends \Gems_Default_RespondentChildActionAbstract
{
    /**
     *
     * @var \Gems_Tracker_Respondent
     */
    private $_respondent;

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
        'browse'        => true,
        'containingId'  => 'autofilter_target',
        'keyboard'      => true,
        'onEmpty'       => 'getOnEmptyText',
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        'extraSort'     => array('grco_created' => SORT_DESC)
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Mail\\Log\\MailLogBrowseSnippet';

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = array(\Gems_Snippets_AutosearchFormSnippet::PERIOD_DATE_USED => 'grco_created');

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $indexParameters = array(
        'contentTitle' => 'getContentTitle',
        );

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Mail\\Log\\RespondentMailLogSearchSnippet');


    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Generic\\CurrentButtonRowSnippet');

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
        $model = $this->loader->getModels()->getCommLogModel($detailed);

        if (! $detailed) {
            $model->addFilter(array(
                'gr2o_patient_nr' => $this->_getParam(\MUtil_Model::REQUEST_ID1),
                'gr2o_id_organization' => $this->_getParam(\MUtil_Model::REQUEST_ID2),
                ));
        }

        return $model;
;
    }

    /**
     * Helper function to get the informed title for the index action.
     *
     * @return $string
     */
    public function getContentTitle()
    {
        $respondent = $this->getRespondent();
        if ($respondent) {
            return sprintf(
                    $this->_('Communication activity log for respondent %s: %s'),
                    $respondent->getPatientNumber(),
                    $respondent->getName()
                    );
        }
        return $this->getIndexTitle();
    }

    /**
     * Get the respondent object
     *
     * @return \Gems_Tracker_Respondent
     */
    public function getRespondent()
    {
        if (! $this->_respondent instanceof \Gems_Tracker_Respondent) {
            if ($this->_getParam(\MUtil_Model::REQUEST_ID1) && $this->_getParam(\MUtil_Model::REQUEST_ID2)) {
                $this->_respondent = parent::getRespondent();

            } else {
                $id = $this->_getParam(\MUtil_Model::REQUEST_ID);

                if ($id) {
                    $model = $this->getModel();
                    $row = $model->loadFirst(array('grco_id_action' => $id));

                    if ($row) {
                        $this->_respondent = $this->loader->getRespondent(
                                $row['gr2o_patient_nr'],
                                $row['gr2o_id_organization']
                                );

                        if (! $this->_respondent->exists) {
                            throw new \Gems_Exception($this->_('Unknown respondent.'));
                        }

                        $this->_respondent->applyToMenuSource($this->menu->getParameterSource());
                    }
                }
            }
        }

        return $this->_respondent;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('communication activity', 'communication activities', $count);
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter = parent::getSearchFilter($useRequest);

        $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db);
        if ($where) {
            $filter[] = $where;
        }

        return $filter;
    }

    /**
     * Resend a log item
     */
    public function resendAction()
    {
        $this->addSnippets('Gems\\Snippets\\Communication\\ResendCommLogItemSnippet');
    }
}
