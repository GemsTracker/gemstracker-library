<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Generic;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 16:06:13
 */
class TextExplanationSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var the tag of the element to create
     */
    protected $explanationTag = 'p';

    /**
     * The text to show
     *
     * @var string
     */
    protected $explanationText;

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
        return \MUtil\Html::create($this->explanationTag, $this->explanationText);
    }
}
