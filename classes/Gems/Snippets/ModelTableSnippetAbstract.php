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
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
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
    protected  array $menuEditRoutes = ['edit'];

    /**
     * Menu routes or routeparts to show in Show box.
     *
     * @var array (int/label => route or routepart)
     */
    protected array $menuShowRoutes = ['show'];

    /**
     * Option to manually diasable the menu
     *
     * @var boolean
     */
    protected bool $showMenu = true;

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
        $keys      = $this->getRouteMaps($metaModel);
        
        if ($metaModel->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            foreach ($this->getShowUrls($bridge, $keys) as $linkParts) {
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
            foreach ($this->getEditUrls($bridge, $keys) as $linkParts) {
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
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return string[]
     */
    protected function getEditUrls(TableBridge $bridge, array $keys): array
    {
        return $this->menuHelper->getLateRelatedUrls($this->menuEditRoutes, $keys, $bridge);
    }

    public function getHtmlOutput()
    {
        $table = parent::getHtmlOutput();
        $table->getOnEmpty()->class = 'centerAlign';

        if ($this->containingId) {
            $this->applyHtmlAttributes($table);
            
            $div = Html::div();
            $div->append($table);
            $div->id = $this->containingId;
            return $div;
        } else {
            return $table;
        }
    }
    
    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        return $metaModel->getMaps();
    }
    
    /**
     * Returns a show menu item, if access is allowed by privileges
     */
    protected function getShowUrls(TableBridge $bridge, array $keys): array
    {
        return $this->menuHelper->getLateRelatedUrls($this->menuShowRoutes, $keys, $bridge);
    }
}
