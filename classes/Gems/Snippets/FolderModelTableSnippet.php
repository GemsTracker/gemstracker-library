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

/**
 * Adds \Gems specific display details and helper functions plus fule buttons:
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
 * @since      Class available since version 1.6.2
 */
class FolderModelTableSnippet extends \MUtil\Snippets\ModelTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser';

    /**
     *
     * @var string The id of a div that contains the table.
     */
    protected $containingId;

    /**
     * Use keyboard to select row
     *
     * @var boolean
     */
    public $keyboard = false;

    /**
     *
     * @var \Gems\Menu
     */
    public $menu;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

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
     *
     * @var \Gems\Util
     */
    protected $util;

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
        // make sure search results are highlighted
        $this->applyTextMarker();

        parent::addBrowseTableColumns($bridge, $model);

        $bridge->getTable()->addColumn(null,$this->_('Action'));
        $td = $bridge->getTable()->tbody()->getLast()->getLast();
        $td->class = 'fileAction';

        foreach ($this->getFileIcons($bridge) as $icon => $item) {
            if (is_array($item)) {
                list($menuItem, $other) = $item;
            } else {
                $menuItem = $item;
                $other    = null;
            }
            $this->addFileImage($td, $bridge, $icon, $menuItem, $other);
        }
    }

    /**
     *
     * @staticvar \MUtil\Html\HtmlElement $blank What to display when blank
     * @param \MUtil\Html\HtmlElement $td      The element / cell to add the conditional link
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param string $icon                    Name of icon file
     * @param \Gems\Menu\SubMenuItem $menuItem The menu item to add
     * @param mixed $options                  Other values for for link (not used for blank)
     * @return void
     */
    protected function addFileImage(\MUtil\Html\HtmlElement $td, \MUtil\Model\Bridge\TableBridge $bridge, $icon, \Gems\Menu\SubMenuItem $menuItem = null, $options = null)
    {
        static $blank;

        if (! $menuItem) {
            return;
        }

        if (! $blank) {
            $blank = \MUtil\Html::create('img', array('src' => 'blank.png', 'alt' => '', 'class' => 'file-icon'));
        }

        $href  = $menuItem->toHRefAttribute($this->request, $bridge);
        $title = array(strtolower($menuItem->get('label')), $bridge->relpath);
        $img  = \MUtil\Html::create('img', array(
            'src'   => $icon,
            'alt'   => $title,
            'class' => 'file-icon',
            ));
        $td->iflink($href, array($href, $img, array('title' => $title), $options), $blank);
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
        $table->tfrow()->pagePanel($paginator, $this->request, $this->translate, array('baseUrl' => $this->baseUrl));
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
            // \MUtil\EchoOut\EchoOut::r('[' . $searchText . ']');
            $marker = new \MUtil\Html\Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
            foreach ($model->getItemNames() as $name) {
                if ($model->get($name, 'label') && (! $model->get($name, 'no_text_search'))) {
                    $model->set($name, 'markCallback', array($marker, 'mark'));
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
        if ($menuItem  = $this->findMenuItem($controller, $action)) {
            return $menuItem->toActionLinkLower($this->request, $parameterSource, $label);
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
    }

    /**
     * Finds a specific active menu item
     *
     * @param string $controller
     * @param string $action
     * @return \Gems\Menu\SubMenuItem
     */
    protected function findMenuItem($controller, $action = 'index')
    {
        return $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));
    }

    /**
     * Get the file icons
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @return array $icon => $menuItem or array($menuItem, $other)
     */
    protected function getFileIcons(\MUtil\Model\Bridge\TableBridge $bridge)
    {
        $onDelete = new \MUtil\Html\OnClickArrayAttribute();
        $onDelete->addConfirm(\MUtil\Lazy::call(
                'sprintf',
                $this->_("Are you sure you want to delete '%s'?"),
                $bridge->relpath
                ));

        return array(
            'process.png'  => $this->findMenuItem($this->request->getControllerName(), 'import'),
            'download.png' => $this->findMenuItem($this->request->getControllerName(), 'download'),
            'eye.png'      => $this->findMenuItem($this->request->getControllerName(), 'show'),
            'edit.png'     => $this->findMenuItem($this->request->getControllerName(), 'edit'),
            'delete.png'   => array(
                $this->findMenuItem($this->request->getControllerName(), 'delete'),
                $onDelete,
                ),
            );
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

        if ($this->containingId || $this->keyboard) {
            $this->applyHtmlAttributes($table);

            $div = \MUtil\Html::create()->div(array('id' => $this->containingId ? $this->containingId : 'keys_target'), $table);

            if ($this->keyboard) {
                return array($div, new \Gems\JQuery\TableRowKeySelector($div));
            } else {
                return $div;
            }
        } else {
            return $table;
        }
    }
}
