<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Gems\Html;
use Gems\MenuNew\RouteHelper;
use MUtil\Html\Raw;

/**
 * Short description for class
 *
 * Long description for class (if any)...
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowRoundSnippetAbstract extends \MUtil\Snippets\ModelVerticalTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    /**
     * Required
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Menu
     */
    public $menu;

    /**
     * @var RouteHelper
     */
    public $routeHelper;

    /**
     *
     * @var int \Gems round id
     */
    protected $roundId;

    /**
     * Show menu buttons below data
     *
     * @var boolean
     */
    protected $showMenu = true;

    /**
     * Show title above data
     *
     * @var boolean
     */
    protected $showTitle = true;

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * @var \Gems\Util
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
        return $this->loader && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        return $this->trackEngine->getRoundModel(true, 'show');
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($this->roundId) {
            $htmlDiv   = \MUtil\Html::div();

            if ($this->showTitle) {
                $htmlDiv->h3(sprintf($this->_('%s round'), $this->trackEngine->getName()));
            }

            $table = parent::getHtmlOutput($view);
            $this->applyHtmlAttributes($table);

            // Make sure deactivated rounds are show as deleted
            foreach ($table->tbody() as $tr) {
                $skip = true;
                foreach ($tr as $td) {
                    if ($skip) {
                        $skip = false;
                    } else {
                        $td->appendAttrib('class', $table->getRepeater()->row_class);
                    }
                }
            }

            if ($this->showMenu) {
                $table->tfrow($this->getMenuList(), array('class' => 'centerAlign'));
            }

            $htmlDiv[] = $table;

            return $htmlDiv;

        } else {
            $this->addMessage($this->_('No round specified.'));
        }
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList()
    {
        $currentRoute = $this->requestInfo->getCurrentRouteResult();
        $currentRouteName = $currentRoute->getMatchedRouteName();
        $routeParameters = $currentRoute->getMatchedParams();
        $routeParts = explode('.', $currentRouteName);
        array_pop($routeParts);
        $routePrefix = join('.', $routeParts);

        $previousRoundId = $this->trackEngine->getPreviousRoundId($this->roundId);
        $nextRoundId = $this->trackEngine->getNextRoundId($this->roundId);

        $actions = [
            [
                'action' => 'show',
                'label' => $this->_('&lt; Previous'),
                'disabled' => ($previousRoundId === null),
                'parameters' => [
                    \Gems\Model::ROUND_ID => $previousRoundId,
                ]
            ],
            [
                'action' => 'index',
                'label' => $this->_('Cancel'),
            ],
            [
                'action' => 'edit',
                'label' => $this->_('Edit'),
            ],
            [
                'action' => 'delete',
                'label' => $this->_('Delete'),
            ],
            [
                'action' => 'show',
                'label' => $this->_('Next &gt;'),
                'disabled' => ($nextRoundId === null),
                'parameters' => [
                    \Gems\Model::ROUND_ID => $nextRoundId,
                ]
            ],
        ];

        $urls = [];
        foreach($actions as $action) {
            if (isset($action['disabled']) && $action['disabled'] === true) {
                $urls[] = Html::actionDisabled(Raw::raw($action['label']));
                continue;
            }
            $routeName = $routePrefix . '.' . $action['action'];
            $route = $this->routeHelper->getRoute($routeName);
            $knownParameters = $routeParameters;
            if (isset($action['parameters'])) {
                $knownParameters = $action['parameters'] + $routeParameters;
            }
            $params = $this->routeHelper->getRouteParamsFromKnownParams($route, $knownParameters);
            $url = $this->routeHelper->getRouteUrl($routeName, $params);
            $urls[] = Html::actionLink($url, Raw::raw($action['label']));
        }

        return $urls;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->trackEngine && (! $this->trackId)) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        if ($this->trackId) {
            // Try to get $this->trackEngine filled
            if (! $this->trackEngine) {
                // Set the engine used
                $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->trackId);
            }

        } else {
            return false;
        }

        if (! $this->roundId) {
            $params = $this->requestInfo->getRequestMatchedParams();
            if (isset($params[\Gems\Model::ROUND_ID])) {
                $this->roundId = $params[\Gems\Model::ROUND_ID];
            }
        }

        return $this->roundId && parent::hasHtmlOutput();
    }
}
