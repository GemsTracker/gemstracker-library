<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Default_TokenAction extends \Gems_Default_TokenSearchActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Token\\RespondentPlanTokenSnippet';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Token\\TokenSearchSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Tracker_TokenStatusLegenda');

    /**
     *
     * @var \Gems_Util
     */
    public $util;

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return sprintf($this->_('Surveys assigned to respondent %s'), $this->_getParam(\MUtil_Model::REQUEST_ID1));
    }

    /**
     * Get the respondent object
     *
     * @return \Gems_Tracker_Respondent
     */
    protected function getRespondent()
    {
        static $respondent;

        if (! $respondent) {
            $patientNumber  = $this->_getParam(\MUtil_Model::REQUEST_ID1);
            $organizationId = $this->_getParam(\MUtil_Model::REQUEST_ID2);

            $respondent = $this->loader->getRespondent($patientNumber, $organizationId);

            if (! $respondent->exists) {
                throw new \Gems_Exception($this->_('Unknown respondent.'));
            }

            $respondent->applyToMenuSource($this->menu->getParameterSource());
        }

        return $respondent;
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        return $this->getRespondent()->getId();
    }

    /**
     * Get the data to use for searching: the values passed in the request + any defaults
     * used in the search form (or any other search request mechanism).
     *
     * It does not return the actual filter used in the query.
     *
     * @see getSearchFilter()
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array
     */
    public function getSearchData($useRequest = true)
    {
        $data = parent::getSearchData($useRequest);

        // Survey action data
        $data['gto_id_respondent']   = $this->getRespondentId();
        $data['gto_id_organization'] = $this->_getParam(\MUtil_Model::REQUEST_ID2);

        return $data;
    }

   /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }
}
