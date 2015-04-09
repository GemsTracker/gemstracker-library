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
 * @subpackage Markup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extends \Zend_Markup with extra utility functions
 *
 * @package    MUtil
 * @subpackage Markup
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Markup extends \Zend_Markup
{
    /**
     * Factory pattern
     *
     * @param  string $parser
     * @param  string $renderer
     * @param  array $options
     * @return \Zend_Markup_Renderer_RendererAbstract
     */
    public static function factory($parser, $renderer = 'Html', array $options = array())
    {
        $parserClass   = self::getParserLoader()->load($parser);
        $rendererClass = self::getRendererLoader()->load($renderer);

        $parser            = new $parserClass();
        $options['parser'] = $parser;
        $renderer          = new $rendererClass($options);

        // Ignore email tags (in any output)
        $renderer->addMarkup(
                'email',
                \Zend_Markup_Renderer_RendererAbstract::TYPE_REPLACE,
                array('start' => '', 'end' => '', 'group' => 'inline')
                );

        return $renderer;
    }

    /**
     * Get the renderer loader
     *
     * @return \Zend_Loader_PluginLoader
     */
    public static function getRendererLoader()
    {
        if (!(self::$_rendererLoader instanceof \Zend_Loader_PluginLoader)) {
            self::$_rendererLoader = new \Zend_Loader_PluginLoader(array(
                'Zend_Markup_Renderer'  => 'Zend/Markup/Renderer/',
                'MUtil_Markup_Renderer' => 'MUtil/Markup/Renderer/',
            ));
        }

        return self::$_rendererLoader;
    }

    public static function render($content, $parser, $renderer = 'Html', array $options = array())
    {
        $renderer = \MUtil_Markup::factory($parser, $renderer, $options);
        return $renderer->render($content);
    }
}
