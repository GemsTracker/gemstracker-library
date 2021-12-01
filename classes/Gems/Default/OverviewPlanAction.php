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

        // The processing of these filters is complicated because
        // 1 - we have a base filter set by the search snippet
        // 2 - we may have additional filter constraints for the Selector snippet
        //
        // So we need to
        // a) set the selector to it's own filter values 
        // b) remove the selector filter from that filter to get the "real" filter
        // c) filter the counts of the selector using that "real" filter
        //   1) for this we create a sub select that can be used in both filters
        //   2) add that filter to the selector
        //   3) add that filter to the output
        // d) add the filter of the clicked selector square to the filter output here
        
        $model    = $this->getModel();
        $selector = $this->getDateSelector();

        // a) set the selector to it's own filter values 
        // b) remove the selector filter from that filter to get the "real" filter
        $realFilter = $selector->processSelectorFilter($this->getRequest(), $filter);

        // c) filter the counts of the selector using that "real" filter
        //   1) for this we create a sub select that can be used in both filters
        $subSelect = $model->getFilteredSelect($realFilter);

        // c) filter the counts of the selector using that "real" filter
        //   2) add that filter to the selector
        $selector->setFilter(['gto_id_token' => $subSelect]);

        // c) filter the counts of the selector using that "real" filter
        //   3) add that filter to the output
        $output['gto_id_token'] = $subSelect;

        // d) add the filter of the clicked selector square to the filter output here
        $output += $selector->getSelectorFilterPart();

        return $output;
    }
}

