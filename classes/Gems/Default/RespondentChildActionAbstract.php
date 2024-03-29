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
 * @since      Class available since version 1.7.1 5-mei-2015 13:15:49
 */
abstract class Gems_Default_RespondentChildActionAbstract extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems_Tracker_Respondent
     */
    private $_respondent;

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * Model level parameters used for all actions, overruled by any values set in any other
     * parameters array except the private $_defaultParamters values in this module.
     *
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $defaultParameters = array(
        'multiTracks' => 'isMultiTracks',
        'respondent' => 'getRespondent',
    );

    /**
     * The parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $importParameters = array('respondent' => null);

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'AutosearchInRespondentSnippet');

    /**
     * Retrieve the error message when a respondent does not exist
     *
     * @return string Use %s to place respondentnumber
     */
    public function getMissingRespondentMessage()
    {
        return $this->_('Respondent %s is not participating at the moment.');
    }
    
    /**
     * Get the respondent object
     *
     * @return \Gems_Tracker_Respondent
     */
    public function getRespondent()
    {
        if (! $this->_respondent) {
            $patientNumber  = $this->_getParam(\MUtil_Model::REQUEST_ID1);
            $organizationId = $this->_getParam(\MUtil_Model::REQUEST_ID2);

            $this->_respondent = $this->loader->getRespondent($patientNumber, $organizationId);

            if ((! $this->_respondent->exists) && $patientNumber && $organizationId) {
                throw new \Gems_Exception(sprintf($this->getMissingRespondentMessage(), $patientNumber));
            }

            if ($this->_respondent->exists && (! array_key_exists($this->_respondent->getOrganizationId(), $this->currentUser->getAllowedOrganizations()))) {
                throw new \Gems_Exception(
                    $this->_('Inaccessible or unknown organization'),
                    403, null,
                    sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->currentUser->getRole()));
            }
            $this->_respondent->applyToMenuSource($this->menu->getParameterSource());
        }

        return $this->_respondent;
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        if ($this->_getParam(\MUtil_Model::REQUEST_ID1)) {
            return $this->getRespondent()->getId();
        }
    }

    /**
     *
     * @return boolean
     */
    protected function isMultiTracks()
    {
        return ! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;
    }
}
