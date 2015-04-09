<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Html_ProgressPanel extends \MUtil_Html_HtmlElement
{
    /**
     * For some elements (e.g. table and tbody) the logical thing to do when content
     * is added that does not have an $_allowedChildTags is to add that content to
     * the last item (i.e. row: tr) instead of adding a new row to the table or element.
     *
     * This is different from the standard behaviour: if you add a non-li item to an ul
     * item it is added in a new li item.
     *
     * @see $_allowedChildTags
     * @see $_lastChild
     *
     * @var boolean When true new content not having a $_allowedChildTags is added to $_lastChild.
     */
    protected $_addtoLastChild = true;

    /**
     * Usually no text is appended after an element, but for certain elements we choose
     * to add a "\n" newline character instead, to keep the output readable in source
     * view.
     *
     * @var string Content added after the element.
     */
    protected $_appendString = "\n";

    /**
     * Default attributes.
     *
     * @var array The actual storage of the attributes.
     */
    protected $_attribs = array(
        'class' => 'ui-progressbar ui-widget ui-widget-content ui-corner-all',
        'id' => 'progress_bar'
    );

    /**
     * When content must contain certain element types only the default child tag contains
     * the tagname of the element that is created to contain the content.
     *
     * When not in $_allowedChildTags the value is added to it in __construct().
     *
     * When empty set to the first value of $_allowedChildTags (if any) in __construct().
     *
     * @see $_allowedChildTags
     *
     * @var string The tagname of the element that should be created for content not having an $_allowedChildTags.
     */
    protected $_defaultChildTag = 'div';

    /**
     * Usually no text is appended before an element, but for certain elements we choose
     * to add a "\n" newline character instead, to keep the output readable in source
     * view.
     *
     * @var string Content added before the element.
     */
    protected $_prependString = "\n";

    /**
     * Class name of inner element that displays text
     *
     * @var string
     */
    public $progressTextClass = 'ui-progressbar-text';

    /**
     * Creates a 'div' progress panel
     *
     * @param mixed $arg_array A \MUtil_Ra::args data collection.
     */
    public function __construct($arg_array = null)
    {
        $args = \MUtil_Ra::args(func_get_args());

        parent::__construct('div', $args);
    }

    /**
     * Returns the tag name for the default child.
     *
     * Exposed as needed by some classes using this class.
     *
     * @see \MUtil_Batch_BatchAbstract
     *
     * @return string
     */
    public function getDefaultChildTag()
    {
        return $this->_defaultChildTag;
    }

    /**
     * Creates a 'div' progress panel
     *
     * @param mixed $arg_array A \MUtil_Ra::args data collection.
     * @return self
     */
    public static function progress($arg_array = null)
    {
        $args = func_get_args();
        return new self($args);
    }

    /**
     * Function to allow overloading of tag rendering only
     *
     * Renders the element tag with it's content into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param \Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    protected function renderElement(\Zend_View_Abstract $view)
    {
        if ($this->_lastChild) {
            $this->_lastChild->class = $this->progressTextClass;

            // These style elements inline because they are REQUIRED to make the panel work.
            //
            // Making the child position absolute means it is positioned over the content that
            // the JQuery progress widget displays (the bar itself) and so this solution allows
            // the text to be displayed over the progress bar (when it has a relative position).
            //
            // The elements should be display neutral.
            //
            $this->_lastChild->style = 'left: 0; height: 100%; position: absolute; top: 0; width: 100%;';
            $this->style = 'position: relative;';
        }

        return parent::renderElement($view);
    }
}
