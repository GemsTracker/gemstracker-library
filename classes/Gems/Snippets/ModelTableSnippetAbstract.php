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

use Gems\Html;
use Gems\MenuNew\MenuSnippetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Marker;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Adds \Gems specific display details and helper functions:
 *
 * Items set are:
 * - Display class: 'browser'
 *
 * Extra helpers are:
 * - Menu helpers:    $this->menu, findMenuItem()
 * - Sort parameters: $sortParamAsc & $sortParamDesc
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class ModelTableSnippetAbstract extends \Zalt\Snippets\ModelTableSnippetAbstract
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
     * Menu routes or routeparts to show in Edit box.
     *
     * @var array (int/label => route or routepart)
     */
    public array $menuEditRoutes = ['edit'];

    /**
     * Menu routes or routeparts to show in Show box.
     *
     * @var array (int/label => route or routepart)
     */
    public array $menuShowRoutes = ['show'];

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

    public function __construct(SnippetOptions $snippetOptions,
                                protected RequestInfo $requestInfo,
                                protected MenuSnippetHelper $menuHelper,
                                TranslatorInterface $translate)
    {
        parent::__construct($snippetOptions, $this->requestInfo, $translate);
    }
    
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();
        $keys      = $metaModel->getKeys();
        
        if ($metaModel->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $showMenuItems = $this->menuHelper->getLateRelatedUrls($this->menuShowRoutes, $keys);
            foreach ($showMenuItems as $linkParts) {
                if (! isset($linkParts['label'])) {
                    $linkParts['label'] = $this->_('Show');
                }
                $bridge->addItemLink(Html::actionLink($linkParts['url'], $linkParts['label']));
            }
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        parent::addBrowseTableColumns($bridge, $dataModel);

        if ($this->showMenu) {
            $editMenuItems = $this->menuHelper->getLateRelatedUrls($this->menuEditRoutes, $keys);
            foreach ($editMenuItems as $linkParts) {
                if (! isset($linkParts['label'])) {
                    $linkParts['label'] = $this->_('Edit');
                }
                $bridge->addItemLink(Html::actionLink($linkParts['url'], $linkParts['label']));
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
    protected function addPaginator($table, \Zend_Paginator $paginator)
    {
        //$table->tfrow()->pagePanel($paginator, $this->request, $this->translate);
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
            $marker = new Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
            foreach ($model->getItemNames() as $name) {
                if ($model->get($name, 'label') && (! $model->is($name, 'no_text_search', true))) {
                    $model->set($name, 'markCallback', [$marker, 'mark']);
                }
            }
        }
    }

    /**
     * Finds a specific active menu item
     *
     * @param string $defaultController
     * @param string|array $actions
     * @return array of \Gems\Menu\SubMenuItem
     */
    protected function findUrls(array|string $actions = ['index'], BridgeInterface $bridge = null)
    {
        $output = [];

        foreach ((array) $actions as $keyOrLabel => $routeNameOrPart) {
            if (str_contains($routeNameOrPart, '.')) {
                $routeName = $routeNameOrPart;
            } else {
                $currentRoute = $this->requestInfo->getRouteName();
                $routeName = substr($currentRoute, 0, strrpos($currentRoute, '.') + 1) . $routeNameOrPart;
            }
            try {
                $route = $this->routeHelper->getRoute($routeName);
            } catch (RouteNotFoundException $e) {
                continue;
            }

            // file_put_contents('data/logs/echo.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . "$keyOrLabel -> $routeNameOrPart <> $routeName\n", FILE_APPEND);
            $output[$keyOrLabel] = $this->routeHelper->getLateRouteUrl($routeName, $bridge->getModel()->getMetaModel()->getKeys());
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
        if ($this->menuEditRoutes) {
            return $this->findUrls($this->menuEditRoutes, $bridge);
        }
        return [];
    }

    public function getHtmlOutput()
    {
        $table = parent::getHtmlOutput();
        $table->getOnEmpty()->class = 'centerAlign';

        if ($this->containingId) {
            $this->applyHtmlAttributes($table);

            return $table;
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
        if ($this->menuShowRoutes) {
            return $this->findUrls($this->menuShowRoutes, $bridge);
        }
        return [];
    }
}
