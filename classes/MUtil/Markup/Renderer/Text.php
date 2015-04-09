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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Markup renderer that outputs the input as flat text.
 *
 * Used e.g. to add a text version to an email of the BB code Html input.
 *
 * @package    MUtil
 * @subpackage Markup
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class MUtil_Markup_Renderer_Text extends \Zend_Markup_Renderer_RendererAbstract
{
    /**
     * Element groups
     *
     * @var array
     */
    protected $_groups = array(
        'block'        => array('block', 'inline', 'block-empty', 'inline-empty', 'list'),
        'inline'       => array('inline', 'inline-empty'),
        'list'         => array('list-item'),
        'list-item'    => array('inline', 'inline-empty', 'list'),
        'block-empty'  => array(),
        'inline-empty' => array(),
    );

    /**
     * The current group
     *
     * @var string
     */
    protected $_group = 'block';

    /**
     * Constructor
     *
     * @param array|\Zend_Config $options
     *
     * @return void
     */
    public function __construct($options = array())
    {
        if ($options instanceof \Zend_Config) {
            $options = $options->toArray();
        }

        $this->_pluginLoader = new \Zend_Loader_PluginLoader(array(
            'MUtil_Markup_Renderer_Text' => 'MUtil/Markup/Renderer/Text/'
        ));

        $this->_defineDefaultMarkups();

        parent::__construct($options);
    }

    /**
     * Define the default markups
     *
     * @return void
     */
    protected function _defineDefaultMarkups()
    {
        $this->_markups = array(
            'b' => array(
                'type'   => 10, // self::TYPE_REPLACE | self::TAG_NORMAL
                'tag'    => 'strong',
                'group'  => 'inline',
                'filter' => true,
            ),
            'u' => array(
                'type'        => 10,
                'tag'         => 'span',
                'group'       => 'inline',
                'filter'      => true,
            ),
            'i' => array(
                'type'   => 10,
                'tag'    => 'em',
                'group'  => 'inline',
                'filter' => true,
            ),
            'cite' => array(
                'type'   => 10,
                'tag'    => 'cite',
                'group'  => 'inline',
                'filter' => true,
            ),
            'del' => array(
                'type'   => 10,
                'tag'    => 'del',
                'group'  => 'inline',
                'filter' => true,
            ),
            'ins' => array(
                'type'   => 10,
                'tag'    => 'ins',
                'group'  => 'inline',
                'filter' => true,
            ),
            'sub' => array(
                'type'   => 10,
                'tag'    => 'sub',
                'group'  => 'inline',
                'filter' => true,
            ),
            'sup' => array(
                'type'   => 10,
                'tag'    => 'sup',
                'group'  => 'inline',
                'filter' => true,
            ),
            'span' => array(
                'type'   => 10,
                'tag'    => 'span',
                'group'  => 'inline',
                'filter' => true,
            ),
            'acronym'  => array(
                'type'   => 10,
                'tag'    => 'acronym',
                'group'  => 'inline',
                'filter' => true,
            ),
            // headings
            'h1' => array(
                'type'   => 10,
                'tag'    => 'h1',
                'group'  => 'inline',
                'filter' => false,
            ),
            'h2' => array(
                'type'   => 10,
                'tag'    => 'h2',
                'group'  => 'inline',
                'filter' => false,
            ),
            'h3' => array(
                'type'   => 10,
                'tag'    => 'h3',
                'group'  => 'inline',
                'filter' => false,
            ),
            'h4' => array(
                'type'   => 10,
                'tag'    => 'h4',
                'group'  => 'inline',
                'filter' => false,
            ),
            'h5' => array(
                'type'   => 10,
                'tag'    => 'h5',
                'group'  => 'inline',
                'filter' => false,
            ),
            'h6' => array(
                'type'   => 10,
                'tag'    => 'h6',
                'group'  => 'inline',
                'filter' => false,
            ),
            // callback tags
            'url' => array(
                'type'     => 6, // self::TYPE_CALLBACK | self::TAG_NORMAL
                'callback' => null,
                'group'    => 'inline',
                'filter'   => true,
            ),
            'img' => array(
                'type'     => 10,
                'group'    => 'inline-empty',
                'filter'   => true,
            ),
            'code' => array(
                'type'     => 10,
                'group'    => 'block-empty',
                'filter'   => false,
            ),
            'p' => array(
                'type'   => 10,
                'tag'    => 'p',
                'group'  => 'block',
                'filter' => true,
            ),
            'ignore' => array(
                'type'   => 10,
                'start'  => '',
                'end'    => '',
                'group'  => 'block-empty',
                'filter' => true,
            ),
            'quote' => array(
                'type'   => 10,
                'tag'    => 'blockquote',
                'group'  => 'block',
                'filter' => true,
            ),
            'list' => array(
                'type'     => 6,
                'callback' => 'MUtil_Markup_Renderer_Text::url',
                'group'    => 'list',
                // 'filter'   => new \Zend_Filter_PregReplace('/.*/is', ''),
            ),
            '*' => array(
                'type'   => 10,
                'tag'    => 'li',
                'group'  => 'list-item',
                'filter' => true,
            ),
            'hr' => array(
                'type'    => 9, // self::TYPE_REPLACE | self::TAG_SINGLE
                'tag'     => 'hr',
                'group'   => 'block',
                'empty'   => true,
            ),
            // aliases
            'bold' => array(
                'type' => 16,
                'name' => 'b',
            ),
            'strong' => array(
                'type' => 16,
                'name' => 'b',
            ),
            'italic' => array(
                'type' => 16,
                'name' => 'i',
            ),
            'em' => array(
                'type' => 16,
                'name' => 'i',
            ),
            'emphasized' => array(
                'type' => 16,
                'name' => 'i',
            ),
            'underline' => array(
                'type' => 16,
                'name' => 'u',
            ),
            'citation' => array(
                'type' => 16,
                'name' => 'cite',
            ),
            'deleted' => array(
                'type' => 16,
                'name' => 'del',
            ),
            'insert' => array(
                'type' => 16,
                'name' => 'ins',
            ),
            'strike' => array(
                'type' => 16,
                'name' => 's',
            ),
            's' => array(
                'type' => 16,
                'name' => 'del',
            ),
            'subscript' => array(
                'type' => 16,
                'name' => 'sub',
            ),
            'superscript' => array(
                'type' => 16,
                'name' => 'sup',
            ),
            'a' => array(
                'type' => 16,
                'name' => 'url',
            ),
            'image' => array(
                'type' => 16,
                'name' => 'img',
            ),
            'li' => array(
                'type' => 16,
                'name' => '*',
            ),
            'color' => array(
                'type' => 16,
                'name' => 'span',
            ),
        );
    }

    /**
     * Execute a replace token
     *
     * @param  \Zend_Markup_Token $token
     * @param  array $markup
     * @return string
     */
    protected function _executeReplace(\Zend_Markup_Token $token, $markup)
    {
        return $this->_render($token);
        if (isset($markup['tag'])) {
            if (!isset($markup['attributes'])) {
                $markup['attributes'] = array();
            }
            $attrs = self::renderAttributes($token, $markup['attributes']);
            return "<{$markup['tag']}{$attrs}>{$this->_render($token)}</{$markup['tag']}>";
        }

        return parent::_executeReplace($token, $markup);
    }

    /**
     * Execute a single replace token
     *
     * @param  \Zend_Markup_Token $token
     * @param  array $markup
     * @return string
     */
    protected function _executeSingleReplace(\Zend_Markup_Token $token, $markup)
    {
        return '';
    }

    /**
     * Set the default filters
     *
     * @return void
     */
    public function addDefaultFilters()
    {
        $this->_defaultFilter = new \Zend_Filter();
    }

    public static function url()
    {
    }
}
