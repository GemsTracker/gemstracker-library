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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * An abstract class for building snippets. Sub classes should override at least
 * getHtmlOutput() or render() to generate output.
 *
 * This class add's to the interface helper variables and functions for:
 * - attribute use: $this->attributes, $this->class & applyHtmlAttributes()
 * - Html creation: $this->getHtmlSequence()
 * - messaging:     $this->_messenger, addMessage() & getMessenger()
 * - rerouting:     $this->resetRoute & redirectRoute()
 * - translation:   $this->translate, _() & plural()
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class MUtil_Snippets_SnippetAbstract extends MUtil_Translate_TranslateableAbstract implements MUtil_Snippets_SnippetInterface
{
    /**
     *
     * @var Zend_Controller_Action_Helper_FlashMessenger
     */
    private $_messenger;

    /**
     * Attributes (e.g. class) for the main html element
     *
     * @var array
     */
    protected $attributes;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class;

    /**
     * Variable to either keep or throw away the request data
     * not specified in the route.
     *
     * @var boolean True then the route is reset
     */
    public $resetRoute = false;

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed $message_args Can be an array or multiple argemuents. Each sub element is a single message string
     * @return self (continuation pattern)
     */
    public function addMessage($message_args)
    {
        $messages  = MUtil_Ra::flatten(func_get_args());
        $messenger = $this->getMessenger();

        foreach ($messages as $message) {
            $messenger->addMessage($message);
        }

        return $this;
    }

    /**
     * Applies the $this=>attributes and $this->class snippet parameters to the
     * $html element.
     *
     * @param MUtil_Html_HtmlElement $html Element to apply the snippet parameters to.
     */
    protected function applyHtmlAttributes(MUtil_Html_HtmlElement $html)
    {
        if ($this->attributes) {
            foreach ($this->attributes as $name => $value) {
                if (! is_numeric($name)) {
                    $html->appendAttrib($name, $value);
                }
            }
        }
        if ($this->class) {
            $html->appendAttrib('class', $this->class);
        }
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        return null;
    }

    /**
     * Helper function for snippets returning a sequence of Html items.
     *
     * @return MUtil_Html_Sequence
     */
    protected function getHtmlSequence()
    {
        return new MUtil_Html_Sequence();
    }

    /**
     * Retrieves the messenger
     *
     * @return Zend_Controller_Action_Helper_FlashMessenger
     */
    protected function getMessenger()
    {
        if (! isset($this->_messenger)) {
            $this->_messenger = new Zend_Controller_Action_Helper_FlashMessenger();
        }
        return $this->_messenger;
    }

    /**
     * When hasHtmlOutput() is false a snippet code user should check
     * for a redirectRoute. Otherwise the redirect calling render() will
     * execute the redirect.
     *
     * This function should never return a value when the snippet does
     * not redirect.
     *
     * Also when hasHtmlOutput() is true this function should not be
     * called.
     *
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    { }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return true;
    }

    /**
     * When there is a redirectRoute this function will execute it.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see Zend_Controller_Action_Helper_Redirector
     */
    public function redirectRoute()
    {
        if ($url = $this->getRedirectRoute()) {
            //MUtil_Echo::track($url);
            $router = new Zend_Controller_Action_Helper_Redirector();
            $router->gotoRouteAndExit($url, null, $this->resetRoute);
        }
    }

    /**
     * Render a string that becomes part of the HtmlOutput of the view
     *
     * You should override either getHtmlOutput() or this function to generate output
     *
     * @param Zend_View_Abstract $view
     * @return string Html output
     */
    public function render(Zend_View_Abstract $view)
    {
        // MUtil_Echo::r(sprintf('Rendering snippet %s.', get_class($this)));
        //
        // TODO: Change snippet workings.
        // All forms are processed twice if hasHtmlOutput() is called here. This is
        // a problem when downloading files.
        // However: not being able to call hasHtmlOutput() twice is not part of the original plan
        // so I gotta rework the forms. :(
        //
        // if ((!$this->hasHtmlOutput()) && $this->getRedirectRoute()) {
        if ($this->getRedirectRoute()) {
            $this->redirectRoute();

        } else {
            $html = $this->getHtmlOutput($view);

            if ($html) {
                if ($html instanceof MUtil_Html_HtmlInterface) {
                    if ($html instanceof MUtil_Html_HtmlElement) {
                        $this->applyHtmlAttributes($html);
                    }
                    return $html->render($view);
                } else {
                    return MUtil_Html::renderAny($view, $html);
                }
            }
        }
    }
}
