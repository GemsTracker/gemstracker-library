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
 * @package    MUtil
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $id: Html.php 362 2011-12-15 17:21:17Z matijsdejong $
 */

/**
 * Collections of static function for using the Html subpackage.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html
{
    /**
     *
     * @var MUtil_Html_Creator
     */
    private static $_creator;

    /**
     *
     * @var MUtil_Html_Renderer
     */
    private static $_renderer;

    /**
     *
     * @var type MUtil_Snippets_SnippetLoader
     */
    private static $_snippetLoader;

    /**
     * Static variable for debuggging purposes. Toggles the echoing of e.g. of sql
     * select statements, using MUtil_Echo.
     *
     * Implemention classes can use this variable to determine whether to display
     * extra debugging information or not. Please be considerate in what you display:
     * be as succint as possible.
     *
     * Use:
     *     MUtil_Html::$verbose = true;
     * to enable.
     *
     * @var boolean $verbose If true echo retrieval statements.
     */
    public static $verbose = false;

    public static function addUrl2Page(Zend_Navigation_Container $menu, $label, $arg_array = null)
    {
        $args = array_slice(func_get_args(), 2);
        $menu->addPage(self::url($args)->toPage($label));
    }

    public static function attrib($attributeName, $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), 1);

        return self::getCreator()->createAttribute($attributeName, $args);
    }

    public static function canRender($value)
    {
        return self::getRenderer()->canRender($value);
    }

    /**
     *
     * @param string $tagName Optional tag to create
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement or MUtil_Html_Creator
     */
    public static function create($tagName = null, $arg_array = null)
    {
        if (null == $tagName) {
            return self::getCreator();
        }

        $args = array_slice(func_get_args(), 1);

        return self::getCreator()->create($tagName, $args);
    }

    public static function createAttribute($attributeName, array $args = array())
    {
        return self::getCreator()->createAttribute($attributeName, $args);
    }

    /**
     * Creates a new HtmlElement with the arguments specfied in a single array.
     *
     * @param string $tagName (or a Lazy object)
     * @param array $args
     * @return MUtil_Html_ElementInterface
     */
    public static function createArray($tagName, array $args = array())
    {
        return self::getCreator()->create($tagName, $args);
    }

    public static function createRaw($tagName, array $args = array())
    {
        return self::getCreator()->createRaw($tagName, $args);
    }

    /**
     * Creates a div element
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement (with div tagName)
     */
    public static function div($arg_array = null)
    {
        $args = func_get_args();

        return self::getCreator()->create('div', $args);
    }

    public static function element2id(Zend_Form_Element $element)
    {
        return self::name2id($element->getName(), $element->getBelongsTo());
    }

    /**
     * Helper function to access the core creator.
     *
     * @return MUtil_Html_Creator
     */
    public static function getCreator()
    {
        if (! self::$_creator) {
            self::$_creator = new MUtil_Html_Creator();
        }

        return self::$_creator;
    }

    /**
     * Returns the class used to perform the actual rendering
     * of objects and items into html.
     *
     * @return MUtil_Html_Renderer
     */
    public static function getRenderer()
    {
        if (! self::$_renderer) {
            self::$_renderer = new MUtil_Html_Renderer();
        }

        return self::$_renderer;
    }

    /**
     * Get the snippet loader for use by self::snippet().
     *
     * @return MUtil_Snippets_SnippetLoader
     */
    public static function getSnippetLoader()
    {
        if (! self::$_snippetLoader) {
            self::setSnippetLoader(new MUtil_Snippets_SnippetLoader());
        }
        return self::$_snippetLoader;
    }

    public static function name2id($name, $belongsTo = null)
    {
        return preg_replace('/\[([^\]]+)\]/', '-$1', $name . '-' . $belongsTo);
    }

    public static function raw($content)
    {
        return self::getCreator()->create('raw', array($content));
    }

    public static function renderAny(Zend_View_Abstract $view, $content)
    {
        return self::getRenderer()->renderAny($view, $content);
    }

    public static function renderArray(Zend_View_Abstract $view, array $content)
    {
        return self::getRenderer()->renderArray($view, $content);
    }

    public static function renderNew(Zend_View_Abstract $view, $tagName, $arg_array = null)
    {
        $args = array_slice(func_get_args(), 2);

        $element = self::getCreator()->create($tagName, $args);

        return $element->render($view);
    }

    /**
     * Creates a table element
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TableElement
     */
    public static function table($arg_array = null)
    {
        $args = func_get_args();

        return self::getCreator()->create('table', $args);
    }

    public static function setCreator(MUtil_Html_Creator $creator)
    {
        self::$_creator = $creator;
        return self::$_creator;
    }

    public static function setRenderer(MUtil_Html_Renderer $renderer)
    {
        self::$_renderer = $renderer;
        return self::$_renderer;
    }

    /**
     * Set the snippet loader for use by self::snippet().
     *
     * @param MUtil_Snippets_SnippetLoader $snippetLoader
     * @return MUtil_Snippets_SnippetLoader
     */
    public static function setSnippetLoader(MUtil_Snippets_SnippetLoader $snippetLoader)
    {
        self::$_snippetLoader = $snippetLoader;
        return self::$_snippetLoader;
    }

    /**
     *
     * @param string $name Snippet name
     * @param MUtil_Ra::pairs $parameter_value_pairs Optional extra snippets
     * @return MUtil_Snippets_SnippetInterface
     */
    public static function snippet($name, $parameter_value_pairs = null)
    {
        if (func_num_args() > 1) {
            $extraSourceParameters = MUtil_Ra::pairs(func_get_args(), 1);
        } else {
            $extraSourceParameters = array();
        }

        $loader = self::getSnippetLoader();

        $snippet = $loader->getSnippet($name, $extraSourceParameters);

        if ($snippet->hasHtmlOutput()) {
            return $snippet;
        }
    }

    public static function url($arg_array = null)
    {
        $args = func_get_args();
        return new MUtil_Html_HrefArrayAttribute($args);
    }
}
