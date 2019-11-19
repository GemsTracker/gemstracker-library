<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Adds Gems specific display details and helper functions plus fule buttons:
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
class Gems_Snippets_FolderModelTableSnippet extends \MUtil_Snippets_ModelTableSnippetAbstract
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
     * @var \Gems_Menu
     */
    public $menu;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
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
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
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
     * @staticvar \MUtil_Html_HtmlElement $blank What to display when blank
     * @param \MUtil_Html_HtmlElement $td      The element / cell to add the conditional link
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param string $icon                    Name of icon file
     * @param \Gems_Menu_SubMenuItem $menuItem The menu item to add
     * @param mixed $options                  Other values for for link (not used for blank)
     * @return void
     */
    protected function addFileImage(\MUtil_Html_HtmlElement $td, \MUtil_Model_Bridge_TableBridge $bridge, $icon, \Gems_Menu_SubMenuItem $menuItem = null, $options = null)
    {
        static $blank;

        if (! $menuItem) {
            return;
        }

        if (! $blank) {
            $blank = \MUtil_Html::create('img', array('src' => 'blank.png', 'alt' => '', 'class' => 'file-icon'));
        }

        $href  = $menuItem->toHRefAttribute($this->request, $bridge);
        $title = array(strtolower($menuItem->get('label')), $bridge->relpath);
        $img  = \MUtil_Html::create('img', array(
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
    protected function addPaginator(\MUtil_Html_TableElement $table, \Zend_Paginator $paginator)
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
            // \MUtil_Echo::r('[' . $searchText . ']');
            $marker = new \MUtil_Html_Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
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
     * @return \MUtil_Html_AElement
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
     * @return \MUtil_Model_ModelAbstract
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
     * @return \Gems_Menu_SubMenuItem
     */
    protected function findMenuItem($controller, $action = 'index')
    {
        return $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));
    }

    /**
     * Get the file icons
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @return array $icon => $menuItem or array($menuItem, $other)
     */
    protected function getFileIcons(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        $onDelete = new \MUtil_Html_OnClickArrayAttribute();
        $onDelete->addConfirm(\MUtil_Lazy::call(
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
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $table = parent::getHtmlOutput($view);
        $table->getOnEmpty()->class = 'centerAlign';

        if ($this->containingId || $this->keyboard) {
            $this->applyHtmlAttributes($table);

            $div = \MUtil_Html::create()->div(array('id' => $this->containingId ? $this->containingId : 'keys_target'), $table);

            if ($this->keyboard) {
                return array($div, new \Gems_JQuery_TableRowKeySelector($div));
            } else {
                return $div;
            }
        } else {
            return $table;
        }
    }
}
