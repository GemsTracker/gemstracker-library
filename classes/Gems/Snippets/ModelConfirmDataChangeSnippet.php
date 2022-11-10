<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use MUtil\Snippets\ModelConfirmDataChangeSnippetAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 30-sep-2015 19:01:28
 */
class ModelConfirmDataChangeSnippet extends ModelConfirmDataChangeSnippetAbstract
{
    /**
     *
     * @var \Gems\AccessLog
     */
    protected $accesslog;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    /**
     * Optional title to display at the head of this page.
     *
     * @var string Optional
     */
    protected $displayTitle;

    /**
     * Required
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        if ($menuItem = $this->getEditMenuItem()) {
            // Add click to edit
            $bridge->tbody()->onclick = array('location.href=\'', $menuItem->toHRefAttribute($this->request), '\';');
        }

        parent::addShowTableRows($bridge, $model);
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
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return \Gems\Menu\SubMenuItem
     */
    protected function getEditMenuItem()
    {
        return $this->findMenuItem($this->request->getControllerName(), 'edit');
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
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $table = parent::getHtmlOutput($view);
        $title = $this->getTitle();

        if ($title) {
            $htmlDiv = \MUtil\Html::div();

            $htmlDiv->h3($title);

            $this->applyHtmlAttributes($table);

            $htmlDiv[] = $table;

            return $htmlDiv;
        } else {
            return $table;
        }
    }

    /**
     * An optional title for the head of the page.
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->displayTitle;
    }

    /**
     * Overrule this function if you want to perform a different
     * action than deleting when the user choose 'yes'.
     */
    protected function performAction()
    {
        parent::performAction();

        $this->accesslog->logChange(
                $this->request,
                $this->getTitle(),
                $this->saveData + $this->getModel()->loadFirst()
                );
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $footer = $bridge->tfrow();

        $footer[] = $this->getQuestion();
        $footer[] = ' ';
        $footer->actionLink(array($this->confirmParameter => 1), $this->_('Yes'));
        $footer[] = ' ';
        $footer->actionLink(array($this->request->getActionKey() => $this->abortAction), $this->_('No'));
    }
}
