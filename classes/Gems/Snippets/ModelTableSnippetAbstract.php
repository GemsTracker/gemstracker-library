<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\MenuNew\RouteHelper;
use Gems\MenuNew\RouteNotFoundException;
use MUtil\Html\AElement;
use MUtil\Lazy\Call;
use MUtil\Model\Bridge\BridgeAbstract;
use MUtil\Model\Bridge\TableBridge;

/**
 * Adds \Gems specific display details and helper functions:
 *
 * Items set are:
 * - Display class: 'browser'
 *
 * Extra helpers are:
 * - Keyboard access: $this->keyboard & getHtmlOutput()
 * - Menu helpers:    $this->menu, findMenuItem()
 * - Sort parameters: $sortParamAsc & $sortParamDesc
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class ModelTableSnippetAbstract extends \MUtil\Snippets\ModelTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser table';

    /**
     *
     * @var string The id of a div that contains the table.
     */
    protected $containingId;

    /**
     * The default search data to use.
     *
     * @var array
     */
    protected $defaultSearchData = [];

    /**
     * Use keyboard to select row
     *
     * @var boolean
     */
    public bool $keyboard = false;

    /**
     * Make sure the keyboard id is used only once
     *
     * @var boolean
     */
    public static $keyboardUsed = false;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = null;

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditActions = ['edit'];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowActions = ['show'];

    /**
     * @var RouteHelper
     */
    protected $routeHelper;

    /**
     * Option to manually diasable the menu
     *
     * @var boolean
     */
    protected bool $showMenu = true;

    /**
     * The $request param that stores the ascending sort
     *
     * @var string
     */
    protected $sortParamAsc = 'asrt';

    /**
     * The $request param that stores the descending sort
     *
     * @var string
     */
    protected $sortParamDesc = 'dsrt';

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $showMenuItems = $this->getShowUrls($bridge);

            foreach ($showMenuItems as $menuItem) {
                $bridge->addItemLink(\Gems\Html::actionLink($menuItem, $this->_('Show')));
            }
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        parent::addBrowseTableColumns($bridge, $model);

        if ($this->showMenu) {
            $editMenuItems = $this->getEditUrls($bridge);

            foreach ($editMenuItems as $menuItem) {
                $bridge->addItemLink(\Gems\Html::actionLink($menuItem, $this->_('Edit')));
            }
        }
    }

    /**
     * Add the paginator panel to the table.
     *
     * Only called when $this->browse is true. Overrule this function
     * to define your own method.
     *
     * $param \Zend_Paginator $paginator
     */
    protected function addPaginator(\MUtil\Html\TableElement $table, \Zend_Paginator $paginator)
    {
        //$table->tfrow()->pagePanel($paginator, $this->request, $this->translate);
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if (! $this->menuActionController) {
            $this->menuActionController = $this->requestInfo->getCurrentController();
        }
    }

    /**
     * Make sure generic search text results are marked
     *
     * @return void
     */
    protected function applyTextMarker()
    {
        $model = $this->getModel();

        $textKey = $model->getTextFilter();
        $filter  = $model->getFilter();

        if (isset($filter[$textKey])) {
            $searchText = $filter[$textKey];
            $marker = new \MUtil\Html\Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
            foreach ($model->getItemNames() as $name) {
                if ($model->get($name, 'label') && (! $model->is($name, 'no_text_search', true))) {
                    $model->set($name, 'markCallback', [$marker, 'mark']);
                }
            }
        }
    }

    /**
     *
     * @param mixed $parameterSource
     * @param string $controller
     * @param string $action
     * @param string $label
     * @return \MUtil\Html\AElement
     */
    public function createMenuLink($parameterSource, $controller, $action = 'index', $label = null)
    {
        $menuItem = $this->findUrls([$action]);
        if ($menuItem) {
            return \Gems\Html::actionLink($menuItem, $this->_('Create'));
        }
        return null;
    }

    /**
     * Finds a specific active menu item
     *
     * @param string $defaultController
     * @param string|array $actions
     * @return array of \Gems\Menu\SubMenuItem
     */
    protected function findUrls(array|string $actions = ['index'], ?TableBridge $bridge = null)
    {
        $output = [];

        foreach ((array) $actions as $key=>$action) {

            try {
                $route = $this->routeHelper->getRoute($action);
            } catch (RouteNotFoundException $e) {
                $currentRouteName = $this->requestInfo->getCurrentRouteResult()->getMatchedRouteName();
                $routeParts = explode('.', $currentRouteName);
                $routeParts[count($routeParts)-1] = $action;
                $routeName = join('.', $routeParts);
                try {
                    $route = $this->routeHelper->getRoute($routeName);
                } catch (RouteNotFoundException $e) {
                    continue;
                }

            }

            $params = [];
            if (isset($route['params'])) {
                $currentParams = $this->requestInfo->getCurrentRouteResult()->getMatchedParams();
                foreach($route['params'] as $paramName) {
                    if (isset($currentParams[$paramName])) {
                        $params[$paramName] = $currentParams[$paramName];
                        continue;
                    }
                    if ($bridge instanceof BridgeAbstract) {
                        $params[$paramName] = $bridge->getLazy($paramName);
                    }
                }
            }

            $output[] = new Call(function(string $routeName, array $params = []) {
                return $this->routeHelper->getRouteUrl($routeName, $params);
            }, [$routeName, $params]);
        }

        return $output;
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return string[]
     */
    protected function getEditUrls(TableBridge $bridge): array
    {
        if ($this->menuEditActions) {
            return $this->findUrls($this->menuEditActions, $bridge);
        }
        return [];
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
        $table = parent::getHtmlOutput($view);
        $table->getOnEmpty()->class = 'centerAlign';

        return $table;

        if (($this->containingId || $this->keyboard) && (! self::$keyboardUsed)) {
            // Assign keyboard tracking only once
            self::$keyboardUsed = true;

            $this->applyHtmlAttributes($table);

            // If we are already in a containing div it is simple
            if ($this->containingId) {
                return [$table, new \Gems\JQuery\TableRowKeySelector($this->containingId)];
            }

            // Create a new containing div
            $div = \MUtil\Html::create()->div(['id' => 'keys_target', 'class' => 'table-container'], $table);

            return [$div, new \Gems\JQuery\TableRowKeySelector($div)];

        } else {
            return $table;
        }
    }

    /**
     * Returns a show menu item, if access is allowed by privileges
     *
     * @return string[]
     */
    protected function getShowUrls(TableBridge $bridge): array
    {
        if ($this->menuShowActions) {
            return $this->findUrls($this->menuShowActions, $bridge);
        }
        return [];
    }
}
