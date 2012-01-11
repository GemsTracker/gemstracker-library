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
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
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
class MUtil_Html_ProgressPanel extends MUtil_Html_HtmlElement
{
    const CODE = "MUtil_Html_ProgressPanel_Code";

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
     * When true the progressbar should start immediately. When false the user has to perform an action.
     *
     * @var boolean
     */
    protected $_autoStart = true;

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
     * Name to prefix the functions, to avoid naming clashes.
     *
     * @var string Default is the classname with an extra underscore
     */
    protected $_functionPrefix;

    /**
     * Usually no text is appended before an element, but for certain elements we choose
     * to add a "\n" newline character instead, to keep the output readable in source
     * view.
     *
     * @var string Content added before the element.
     */
    protected $_prependString = "\n";

    /**
     *
     * @var Zend_ProgressBar
     */
    protected $_progressBar;

    /**
     *
     * @var Zend_ProgressBar_Adapter
     */
    protected $_progressBarAdapter;

    /**
     * Extra array with special types for subclasses.
     *
     * When an object of one of the key types is used, then use
     * the class method defined as the value.
     *
     * @see $_specialTypesDefault
     *
     * @var array Of 'class or interfacename' => 'class method' of null
     */
    protected $_specialTypes = array(
        'Zend_ProgressBar' => 'setProgressBar',
        'Zend_ProgressBar_Adapter' => 'setProgressBarAdapter',
        );

    /**
     * The mode to use for the panel: push or pull
     *
     * @var string
     */
    public $method = 'Pull';

    /**
     * The name of the parameter used for progress panel signals
     *
     * @var string
     */
    public $progressParameterName = 'progress';

    /**
     * The value required for the progress panel to start running
     *
     * @var string
     */
    public $progressParameterRunValue = 'run';

    /**
     * Class name of inner element that displays text
     *
     * @var string
     */
    public $progressTextClass = 'ui-progressbar-text';

    /**
     * Creates a 'div' progress panel
     *
     * @param mixed $arg_array A MUtil_Ra::args data collection.
     */
    public function __construct($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        parent::__construct('div', $args);
    }

    /**
     * Update the progess panel
     *
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function finish()
    {
        $bar = $this->getProgressBar();
        $bar->finish();

        return $this;
    }
    /**
     * Returns the JavaScript object associated with this object.
     *
     * WARNING: calling this object sets it's position in the order the
     * objects are rendered. If you use MUtil_Lazy objects, make sure they
     * have the correct value when rendering.
     *
     * @return MUtil_Html_Code_JavaScript
     */
    public function getCode()
    {
        if (! isset($this->_content[self::CODE])) {
            $js = new MUtil_Html_Code_JavaScript(dirname(__FILE__) . '/ProgressPanel' . $this->method . '.js');
            $js->setInHeader(false);

            $this->_content[self::CODE] = $js;
        }

        return $this->_content[self::CODE];
    }

    /**
     * Returns the prefix used for the function names to avoid naming clashes.
     *
     * @return string
     */
    public function getFunctionPrefix()
    {
        if (! $this->_functionPrefix) {
            $this->setFunctionPrefix(__CLASS__ . '_' . $this->getAttrib('id'). '_');
        }

        return (string) $this->_functionPrefix;
    }

    /**
     *
     * @return Zend_ProgressBar
     */
    public function getProgressBar()
    {
        if (! $this->_progressBar instanceof Zend_ProgressBar) {
            $this->setProgressBar(new Zend_ProgressBar($this->getProgressBarAdapter(), 0, 100));
        }
        return $this->_progressBar;
    }

    /**
     *
     * @return Zend_ProgressBar_Adapter
     */
    public function getProgressBarAdapter()
    {
        if (! $this->_progressBarAdapter instanceof Zend_ProgressBar_Adapter) {
            if ($this->method == 'Pull') {
                $this->setProgressBarAdapter(new Zend_ProgressBar_Adapter_JsPull());
            } else {
                $this->setProgressBarAdapter(new Zend_ProgressBar_Adapter_JsPush());
            }
        }

        return $this->_progressBarAdapter;
    }

    /**
     * Creates a 'div' progress panel
     *
     * @param mixed $arg_array A MUtil_Ra::args data collection.
     * @return self
     */
    public static function progress($arg_array = null)
    {
        $args = func_get_args();
        return new self($args);
    }

    /**
     * Function to allow overloading  of tag rendering only
     *
     * Renders the element tag with it's content into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    protected function renderElement(Zend_View_Abstract $view)
    {
        ZendX_JQuery::enableView($view);

        if ($this->getProgressBarAdapter() instanceof Zend_ProgressBar_Adapter) {
            $js = $this->getCode();

            // Set the fields, in case they where not set earlier
            $js->setDefault('__AUTOSTART__', $this->_autoStart ? 'true' : 'false');
            $js->setDefault('{ID}', $this->getAttrib('id'));
            $js->setDefault('{TEXT_TAG}', $this->_defaultChildTag);
            $js->setDefault('{TEXT_CLASS}', $this->progressTextClass);
            $js->setDefault('{URL}', addcslashes($view->url(array($this->progressParameterName => $this->progressParameterRunValue)), "/"));
        }

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

    /**
     * Checks whether the progress panel should be running
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return boolean
     */
    public function run(Zend_Controller_Request_Abstract $request)
    {
        return $request->getParam($this->progressParameterName) === $this->progressParameterRunValue;
    }

    /**
     * Name prefix for functions.
     *
     * Set automatically to __CLASS___, use different name
     * in case of name clashes.
     *
     * @param string $prefix
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function setFunctionPrefix($prefix)
    {
        $this->_functionPrefix = $prefix;
        return $this;
    }

    /**
     *
     * @param Zend_ProgressBar $progressBar
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function setProgressBar(Zend_ProgressBar $progressBar)
    {
        $this->_progressBar = $progressBar;
        return $this;
    }

    /**
     *
     * @param Zend_ProgressBar_Adapter_Interface $adapter
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function setProgressBarAdapter(Zend_ProgressBar_Adapter $adapter)
    {
        if ($adapter instanceof Zend_ProgressBar_Adapter_JsPush) {
            $js = $this->getCode();
            $prefix = $this->getFunctionPrefix();

            // Set the fields, in case they where not set earlier
            $js->setDefault('FUNCTION_PREFIX_', $prefix);
            $adapter->setUpdateMethodName($prefix . 'Update');
            $adapter->setFinishMethodName($prefix . 'Finish');
        }

        $this->_progressBarAdapter = $adapter;
        return $this;
    }

    /**
     * Update the progess panel
     *
     * @param int $value
     * @param string $text
     * @return MUtil_Html_ProgressPanel (continuation pattern)
     */
    public function update($value, $text = null)
    {
        $bar = $this->getProgressBar();
        $bar->update($value, $text);

        return $this;
    }
}
