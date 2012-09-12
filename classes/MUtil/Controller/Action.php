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
 * @package MUtil
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extends Zend_Controller_Action with basic functionality and MUtil_Html
 *
 * Basic functionality provided:
 *  - title attribute for use in htm/head/title element
 *  - flashMessenger use standardised and simplified
 *  - use of Zend_Translate simplified and shortened in code
 *  - disable Zend_Layout and Zend_View with initRawOutput() and $useRawOutput.
 *
 * MUtil_Html functionality provided:
 *  - semi automatic MUtil_Html_Sequence initiation
 *  - view script set to html-view.phtml when using html
 *  - snippet usage for repeatably used snippets of html on a page
 *
 * @package MUtil
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Controller_Action extends Zend_Controller_Action
{
    /**
     * Created when $useHtmlView is true or initHtml() is run.
     *
     * Allows you to create html using e.g. $this->html->p();
     *
     * @var MUtil_Html_Sequence $html The html object to add content to.
     */
    public $html;

    /**
     * The loader for snippets.
     *
     * @var MUtil_Snippets_SnippetLoader
     */
    protected $snippetLoader;

    /**
     * The current html/head/title for this page.
     *
     * Can be a string or an array of string values.
     *
     * var string|array $title;
     */
    protected $title;

    /**
     * Set in init() from Zend_Registry::get('Zend_Translate'), unless set already.
     *
     * The code will use a Potemkin Translate adapter when Zend_Translate is not set in the registry, so
     * the code will still work, it just will not translate.
     *
     * @var Zend_Translate $translate
     */
    public $translate;

    /**
     * Set to true in child class for automatic creation of $this->html.
     *
     * To initiate the use of $this->html from the code call $this->initHtml()
     *
     * Overrules $useRawOutput.
     *
     * @see $useRawOutput
     * @var boolean $useHtmlView
     */
    public $useHtmlView = false;

    /**
     * Set to true in child class for automatic use of raw (e.g. echo) output only.
     *
     * Otherwise call $this->initRawOutput() to switch to raw echo output.
     *
     * Overruled in initialization if $useHtmlView is true.
     *
     * @see $useHtmlView
     * @var boolean $useRawOutput
     */
    public $useRawOutput = false;

    /**
     * A ssession based message store.
     *
     * Standard the flash messenger for storing messages
     *
     * @var Zend_Controller_Action_Helper_FlashMessenger
     */
    private $_messenger;

    /**
     * Copy from Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        return $this->translate->_($text, $locale);
    }

    /**
     * Reroutes the page (i.e. header('Location: ');)
     *
     * @param array $urlOptions Url parts
     * @param boolean $reset Use default module, action and controller instead of current when not specified in $urlOptions
     * @param string $routeName
     * @param boolean $encode
     */
    protected function _reroute(array $urlOptions = array(), $reset = false, $routeName = null, $encode = true)
    {
        if ($reset) {
            // MUtil_Echo::r($urlOptions, 'before');
            $urlOptions = MUtil_Html_UrlArrayAttribute::rerouteUrl($this->getRequest(), $urlOptions);
            // MUtil_Echo::r($urlOptions, 'after');
        }
        $this->_helper->redirector->gotoRoute($urlOptions, $routeName, $reset, $encode);
    }

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed $message_args Can be an array or multiple argemuents. Each sub element is a single message string
     * @return MUtil_Controller_Action
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
     * Searches and loads a .php snippet file and adds the content to $this->html.
     *
     * @param string $filename The name of the snippet
     * @param MUtil_Ra::pairs $parameter_value_pairs name/value pairs ot add to the source for this snippet
     * @return MUtil_Snippets_SnippetInterface The snippet if content was possibly added.
     */
    public function addSnippet($filename, $parameter_value_pairs = null)
    {
        $extraSource = MUtil_Ra::pairs(func_get_args(), 1);
        $results     = $this->addSnippets($filename, $extraSource);
        return $results ? reset($results) : false;
    }

    /**
     * Searches and loads multiple .php snippet files and adds them to this->html using the filename as
     * content key, unless that key already exists.
     *
     * @param array $filenames Names of snippets
     * @param MUtil_Ra::pairs $parameter_value_pairs name/value pairs ot add to the source for this snippet
     * @return mixed The snippet if content was possibly added.
     */
    public function addSnippets($filenames, $parameter_value_pairs = null)
    {
        if ($filenames) {
            $extraSource = MUtil_Ra::pairs(func_get_args(), 1);

            $results  = array();
            $snippets = $this->getSnippets($filenames, $extraSource);
            foreach ($snippets as $filename => $snippet) {

                if ($snippet->hasHtmlOutput()) {
                    if (isset($this->html[$filename])) {
                        $this->html[] = $snippet;
                    } else {
                        $this->html[$filename] = $snippet;
                    }
                    $results[$filename]    = $snippet;

                } elseif ($snippet->getRedirectRoute()) {
                    $snippet->redirectRoute();
                    return false;
                }
            }

            return $results;
        }
    }

    /**
     * Appends an extra part to the html/head/title.
     *
     * Forces $this->title to be an array.
     *
     * @param <type> $extraTitle
     * @return MUtil_Controller_Action
     */
    public function appendTitle($extraTitle)
    {
        if ($this->title && (! is_array($this->title))) {
            $this->title = array($this->title);
        }
        $this->title[] = $extraTitle;

        return $this;
    }

    /**
     * Disable the use of Zend_Layout
     *
     * @return Zend_Controller_Action (continuation pattern)
     */
    public function disableLayout()
    {
        // Actually I would like a check if there is a
        // layout instance in the first place.
        $layout = Zend_Layout::getMvcInstance();
        if ($layout instanceof Zend_Layout) {
            $layout->disableLayout();
        }
        // Zend_Layout::resetMvcInstance();

        return $this;
    }

    /**
     * Returns a session based message store for adding messages to.
     *
     * @return Zend_Controller_Action_Helper_FlashMessenger
     */
    public function getMessenger()
    {
        if (! $this->_messenger) {
            $this->setMessenger($this->_helper->getHelper('FlashMessenger'));
        }

        return $this->_messenger;
    }

    /**
     * Searches and loads a .php snippet file.
     *
     * @param string $filename The name of the snippet
     * @param MUtil_Ra::pairs $parameter_value_pairs name/value pairs ot add to the source for this snippet
     * @return MUtil_Snippets_SnippetInterface The snippet
     */
    public function getSnippet($filename, $parameter_value_pairs = null)
    {
        $extraSource = MUtil_Ra::pairs(func_get_args(), 1);
        $results     = $this->getSnippets($filename, $extraSource);
        return reset($results);
    }

    /**
     * Searches and loads multiple .php snippet file.
     *
     * @param string $filenames Array of snippet names with optionally extra parameters included
     * @param MUtil_Ra::pairs $parameter_value_pairs name/value pairs ot add to the source for this snippet
     * @return array Of filename => MUtil_Snippets_SnippetInterface snippets
     */
    public function getSnippets($filenames, $parameter_value_pairs = null)
    {
        if (func_num_args() > 1) {
            $extraSourceParameters = MUtil_Ra::pairs(func_get_args(), 1);
        } else {
            $extraSourceParameters = array();
        }

        if (is_array($filenames)) {
            list($filenames, $params) = MUtil_Ra::keySplit($filenames);

            if ($params) {
                $extraSourceParameters = $params + $extraSourceParameters;
            }
        } else {
            $filenames = array($filenames);
        }

        $results = array();

        if ($filenames) {
            $loader = $this->getSnippetLoader();

            foreach ($filenames as $filename) {
                $results[$filename] = $loader->getSnippet($filename, $extraSourceParameters);
            }
        }

        return $results;
    }

    /**
     * Returns a source of values for snippets.
     *
     * @return MUtil_Snippets_SnippetLoader
     */
    public function getSnippetLoader()
    {
        if (! $this->snippetLoader) {
            $this->loadSnippetLoader();
        }

        return $this->snippetLoader;
    }

    /**
     * Returns the current html/head/title for this page.
     *
     * If the title is an array the seperator concatenates the parts.
     *
     * @param string $separator
     * @return string
     */
    public function getTitle($separator = '')
    {
        if (is_array($this->title)) {
            return implode($separator, $this->title);
        } else {
            return $this->title;
        }
    }

    /**
     * Returns the translator.
     *
     * Set the translator if not yet set. The default translator is
     * Zend_Registry::get('Zend_Translate') or a Potemkin Translate adapter
     * when not set in the registry, so the code will still work, it just
     * will not translate.
     *
     * @return Zend_Translate
     */
    public function getTranslate()
    {
        if (! $this->translate) {
            $translate = Zend_Registry::get('Zend_Translate');

            if (null === $translate) {
                // Make sure there always is a translator
                $translate = new MUtil_Translate_Adapter_Potemkin();
                Zend_Registry::set('Zend_Translate', $translate);
            }

            $this->setTranslate($translate);
        }

        return $this->translate;
    }

    /*
    public function getViewSource()
    {
        $file_name = $this->view->viewRenderer->getScriptAction();
        return $this->view->getScriptPath($file_name ? $file_name . '.phtml' : null);
    } // */

    /**
     * Initialize translate and html objects
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        if (! $this->translate) {
            $this->getTranslate();
        }

        if ($this->useHtmlView) {
            $this->initHtml();
        } elseif ($this->useRawOutput) {
            $this->initRawOutput();
        }
    }

    /**
     * Intializes the html component.
     *
     * @param boolean $reset Throws away any existing html output when true
     * @return void
     */
    public function initHtml($reset = false)
    {
        if ($reset || (! $this->html)) {
            MUtil_Html::setSnippetLoader($this->getSnippetLoader());

            $this->html = new MUtil_Html_Sequence();

            // Add this variable to the view.
            $this->view->html = $this->html;

            // Load html-view.phtml from the same directory as this file.
            $this->view->setScriptPath(dirname(__FILE__));
            $this->_helper->viewRenderer->setNoController();
            $this->_helper->viewRenderer->setScriptAction('html-view');

            $this->useHtmlView  = true;
            $this->useRawOutput = false;
        }
    }

    /**
     * Intializes the raw (echo) output component.
     *
     * @return void
     */
    public function initRawOutput()
    {
        // Disable layout ((if any)
        $this->disableLayout();

        // Set view rendering off
        $this->_helper->viewRenderer->setNoRender(true);

        $this->useHtmlView  = false;
        $this->useRawOutput = true;
    }

    /**
     * Stub for overruling default snippet loader initiation.
     */
    protected function loadSnippetLoader()
    {
        // Create the snippet with this controller as the parameter source
        $this->snippetLoader = new MUtil_Snippets_SnippetLoader($this);
    }


    /**
     * Copy from Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->translate, 'plural'), $args);
    }

     /* currently not in use
    public function setLayout($scriptFileName)
    {
        $this->layout->setLayout($scriptFileName);
    } // */

    /**
     * Set the session based message store.
     *
     * @param Zend_Controller_Action_Helper_FlashMessenger $messenger
     * @return MUtil_Controller_Action
     */
    public function setMessenger(Zend_Controller_Action_Helper_FlashMessenger $messenger)
    {
        $this->_messenger = $messenger;
        $this->view->messenger = $messenger;

        return $this;
    }


    /**
     * Set the html/head/title for this page. Can be a string or an array of string values.
     *
     * @param string|array $title;
     * @return MUtil_Controller_Action
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets the translator
     *
     * @param Zend_Translate $translate
     * @return MUtil_Controller_Action
     */
    public function setTranslate(Zend_Translate $translate)
    {
        $this->translate = $translate;

        return $this;
    }
}