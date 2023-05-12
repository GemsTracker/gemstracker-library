<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Jasper van Gestel <jvangestl@gmail.com>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

/**
 * Abstract class for quickly creating a tabbed bar, or rather a div that contains a number
 * of links, adding specific classes for display.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
abstract class HorizontalScrollMenuSnippetAbstract extends \MUtil\Snippets\TabSnippetAbstract
{
    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'horizontal_scroll_menu';

    /**
     *
     * @var string Label of the next button
     */
    protected $nextLabel = '>';

    /**
     *
     * @var string Label of the previous button
     */
    protected $prevLabel = '<';

    /**
     *
     * @var int Show scroll controls from this number of tabs
     */
    protected $scrollFromSize = 4;

    /**
     *
     * @var int Length of a label before it is cut off
     */
    protected $tabLabelLength = 20;

    /**
     *
     * @var string String that is added to a cut off label
     */
    protected $tabLabelCutOffString = '...';

    /**
     *
     * @var \Zend_View
     */
    protected $view;

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
        $tabs = $this->getTabs();

        $tabCount = count($tabs);

        if ($tabs && ($this->displaySingleTab || $tabCount > 1)) {
            // Is there a better helper to include JS?
            $view->headScript()->appendFile($this->basepath->getBasePath()  .  '/gems/js/jquery.horizontalScrollMenu.js');

            $script = '(function($) {$(".'.$this->class.'").horizontalScrollMenu();}(jQuery));';

            $view->inlineScript()->appendScript($script);

            // Set the correct parameters
            $this->getCurrentTab();


            $scrollContainer = \MUtil\Html::create()->div();

            if ($tabCount > $this->scrollFromSize) {
                $scrollContainer->a('#', $this->prevLabel, array('class' => 'prev'));
            } else {
                $scrollContainer->span(array('class' => 'prev disabled'))
                        ->raw(str_repeat('&nbsp', strlen($this->prevLabel)));
            }

            $tabRow = $scrollContainer->div(array('class' => 'container'))->ul();

            foreach ($tabs as $tabId => $content) {
                $li = $tabRow->li(array('class' => $this->tabClass));

                if (strlen($content) > $this->tabLabelLength) {
                    $content = substr($content, 0, $this->tabLabelLength) . $this->tabLabelCutOffString;
                }

                $li->a($this->getParameterKeysFor($tabId) + $this->href, $content);

                if ($tabId == $this->currentTab) {
                    $li->appendAttrib('class', $this->tabActiveClass);
                }
            }

            if ($tabCount > $this->scrollFromSize) {
                $scrollContainer->a('#', $this->nextLabel, array('class' => 'next'));
            } else {
                $scrollContainer->span(array('class' => 'next disabled'))
                        ->raw(str_repeat('&nbsp', strlen($this->nextLabel)));
            }

            return $scrollContainer;
        } else {
            return null;
        }
    }
}
