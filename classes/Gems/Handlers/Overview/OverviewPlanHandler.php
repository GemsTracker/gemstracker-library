<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Overview;

use Gems\Db\ResultFetcher;
use Gems\MenuNew\RouteHelper;
use Gems\Selector\TokenDateSelector;
use Gems\Tracker;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class OverviewPlanHandler extends TokenSearchHandlerAbstract
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
    protected array $autofilterParameters = array(
        'dateSelector' => 'getDateSelector',
        'surveyReturn' => 'setSurveyReturn',
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Token\\TokenDateSelectorSnippet', 'Token\\PlanTokenSnippet'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Token\\OverviewSearchSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        Tracker $tracker,
        ResultFetcher $resultFetcher,
        protected RouteHelper $routeHelper,
        protected TokenDateSelector $dateSelector,
    ) {
        parent::__construct($responder, $translate, $tracker, $resultFetcher);
    }

    /**
     *
     * @return \Gems\Selector\DateSelectorAbstract
     */
    public function getDateSelector()
    {
        return $this->dateSelector;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
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
    public function getSearchDefaults(): array
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
    public function getSearchFilter(bool $useRequest = true): array
    {
        $filter = parent::getSearchFilter($useRequest);

        // The processing of these filters is complicated because
        // 1 - we have a base filter set by the search snippet
        // 2 - we may have additional filter constraints for the Selector snippet
        //
        // So we need to
        // a) set the selector to its own filter values
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
        $realFilter = $selector->processSelectorFilter($this->requestInfo, $filter);

        // Remove non-columns
        foreach ($realFilter as $key => $value) {
            if (! (is_integer($key) || $model->has($key))) {
                unset($realFilter[$key]);
            }
        }
        
        // c) filter the counts of the selector using that "real" filter
        //   1) for this we create a sub select that can be used in both filters
        $subSelect = $model->getFilteredSelect($realFilter);

        // c) filter the counts of the selector using that "real" filter
        //   2) add that filter to the selector
        $selector->setFilter([sprintf('gto_id_token IN (%s)', (string) $subSelect)]); // \MUtil 1.9.1 does not support passing on a Select object
        // $selector->setFilter(['gto_id_token' => $subSelect]);

        // c) filter the counts of the selector using that "real" filter
        //   3) add that filter to the output
        $output[] = sprintf('gto_id_token IN (%s)', (string) $subSelect); // \MUtil 1.9.1 does not support passing on a Select object
        // $output['gto_id_token'] = $subSelect;

        // d) add the filter of the clicked selector square to the filter output here
        $output = array_merge($selector->getSelectorFilterPart(), $output);

        return $output;
    }
}

