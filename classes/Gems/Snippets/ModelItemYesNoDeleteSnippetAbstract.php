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

use MUtil\Model\Bridge\VerticalTableBridge;
use MUtil\Model\ModelAbstract;

/**
 * Ask Yes/No conformation for deletion and deletes item when confirmed.
 *
 * Can be used for other uses than delete by overriding performAction().
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
abstract class ModelItemYesNoDeleteSnippetAbstract extends \MUtil\Snippets\ModelYesNoDeleteSnippetAbstract
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
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(VerticalTableBridge $bridge, ModelAbstract $model)
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
        return $this->menu->find(['controller' => $controller, 'action' => $action, 'allowed' => true]);
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return \Gems\Menu\SubMenuItem
     */
    protected function getEditMenuItem()
    {
        return null; //$this->findMenuItem($this->request->getControllerName(), 'edit');
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
        if ($table = parent::getHtmlOutput($view)) {
            if ($title = $this->getTitle()) {
                $htmlDiv = \MUtil\Html::div();

                $htmlDiv->h3($title);

                $this->applyHtmlAttributes($table);

                $htmlDiv[] = $table;

                return $htmlDiv;
            } else {
                return $table;
            }
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
        $data = $this->getModel()->loadFirst();

        parent::performAction();

        $this->accesslog->logChange($this->request, $this->getTitle(), $data);
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
    protected function setShowTableFooter(VerticalTableBridge $bridge, ModelAbstract $model)
    {
        $footer = $bridge->tfrow();

        $footer[] = $this->getQuestion();
        $footer[] = ' ';
        $footer->actionLink([$this->confirmParameter => 1], $this->_('Yes'));
        $footer[] = ' ';
        $footer->actionLink(['action' => $this->abortAction], $this->_('No'));
    }
}
