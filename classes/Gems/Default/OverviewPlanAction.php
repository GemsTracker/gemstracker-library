<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_OverviewPlanAction extends \Gems_Default_TokenSearchActionAbstract
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
        'dateSelector' => 'getDateSelector',
        'multiTracks'  => 'isMultiTracks',
        'surveyReturn' => 'setSurveyReturn',
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = array('Token\\TokenDateSelectorSnippet', 'Token\\PlanTokenSnippet');

    /**
     *
     * @var \Gems_Selector_DateSelectorAbstract
     */
    public $dateSelector;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Token\\OverviewSearchSnippet');

    /**
     *
     * @return \Gems_Selector_DateSelectorAbstract
     */
    public function getDateSelector()
    {
        if (! $this->dateSelector) {
            $this->dateSelector = $this->loader->getSelector()->getTokenDateSelector();
        }

        return $this->dateSelector;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Planning overview');
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
            $this->defaultSearchData = $this->getDateSelector()->getDefaultSearchData();
        }

        return $this->defaultSearchData;
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

        $selector = $this->getDateSelector();
        $output = $selector->getFilter($this->request, $filter);

        // \MUtil_Echo::track($filter, $output);

        return $output;
    }
}

