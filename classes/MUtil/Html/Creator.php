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
 * @version    $id: Creator.php 362 2011-12-15 17:21:17Z matijsdejong $
 */

/**
 * Class for storing references for creating html attributes, elements and other objects.
 *
 * Basically this class stores list of element and attributes names that should be treated
 * in different from just creating the most basic of element types.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */

class MUtil_Html_Creator
{
    /**
     *
     * @var MUtil_Util_LookupList
     */
    protected $_attributeFunctionList;

    /**
     *
     * @var MUtil_Util_LookupList
     */
    protected $_elementFunctionList;

    /**
     *
     * @var array
     */
    protected $_initialAttributeFunctions = array(
        'href'    => 'MUtil_Html_HrefArrayAttribute::hrefAttribute',
        'onclick' => 'MUtil_Html_OnClickArrayAttribute::onclickAttribute',
        'src'     => 'MUtil_Html_SrcArrayAttribute::srcAttribute',
        'style'   => 'MUtil_Html_StyleArrayAttribute::styleAttribute',
    );

    /**
     *
     * @var array
     */
    protected $_initalElementFunctions = array(
        'a'                 => 'MUtil_Html_AElement::a',
        'array'             => 'MUtil_Html_Sequence::createSequence',
        'call'              => 'MUtil_Lazy::call',
        'col'               => 'MUtil_Html_ColElement::col',
        'colgroup'          => 'MUtil_Html_ColGroupElement::colgroup',
        'dir'               => 'MUtil_Html_ListElement::dir',
        'dd'                => 'MUtil_Html_DdElement::dd',
        'dl'                => 'MUtil_Html_DlElement::dl',
        'dt'                => 'MUtil_Html_DtElement::dt',
        'echo'              => 'MUtil_Html_TableElement::createVar',
        'email'             => 'MUtil_Html_AElement::email',
        'if'                => 'MUtil_Lazy::iff',
        'iflink'            => 'MUtil_Html_AElement::iflink',
        'ifmail'            => 'MUtil_Html_AElement::ifmail',
        'img'               => 'MUtil_Html_ImgElement::img',
        'image'             => 'MUtil_Html_ImgElement::img',
        'input'             => 'MUtil_Html_InputRenderer::input',
        'inputComplete'     => 'MUtil_Html_InputRenderer::inputComplete',
        'inputDescription'  => 'MUtil_Html_InputRenderer::inputDescription',
        'inputDisplayGroup' => 'MUtil_Html_InputRenderer::inputDisplayGroup',
        'inputElement'      => 'MUtil_Html_InputRenderer::inputElement',
        'inputErrors'       => 'MUtil_Html_InputRenderer::inputErrors',
        'inputExcept'       => 'MUtil_Html_InputRenderer::inputExcept',
        'inputForm'         => 'MUtil_Html_InputRenderer::inputForm',
        'inputLabel'        => 'MUtil_Html_LabelElement::label',
        'inputOnly'         => 'MUtil_Html_InputRenderer::inputOnly',
        'inputOnlyArray'    => 'MUtil_Html_InputRenderer::inputOnlyArray',
        'inputUntil'        => 'MUtil_Html_InputRenderer::inputUntil',
        'inputUpto'         => 'MUtil_Html_InputRenderer::inputUpto',
        'label'             => 'MUtil_Html_LabelElement::label',
        'menu'              => 'MUtil_Html_ListElement::menu',
        'ol'                => 'MUtil_Html_ListElement::ol',
        'pagePanel'         => 'MUtil_Html_PagePanel::pagePanel',
        'progress'          => 'MUtil_Html_ProgressPanel::progress',
        'progressPanel'     => 'MUtil_Html_ProgressPanel::progress',
        'raw'               => 'MUtil_Html_Raw::raw',
        'seq'               => 'MUtil_Html_Sequence::createSequence',
        'sequence'          => 'MUtil_Html_Sequence::createSequence',   // A sequence can contain another sequence, so other function name used
        'snippet'           => 'MUtil_Html::snippet',
        'spaced'            => 'MUtil_Html_Sequence::createSpaced',     // A sequence can contain another sequence, so other function name used
        'table'             => 'MUtil_Html_TableElement::table',
        'tbody'             => 'MUtil_Html_TBodyElement::tbody',
        'tfoot'             => 'MUtil_Html_TBodyElement::tfoot',
        'thead'             => 'MUtil_Html_TBodyElement::thead',
        'tr'                => 'MUtil_Html_TrElement::tr',
        'ul'                => 'MUtil_Html_ListElement::ul',
    );

    public function __call($name, array $arguments)
    {
        return $this->create($name, $arguments);
    }

    public function __construct($elementFunctions = null, $attributeFunctions = null, $append = true)
    {
        $this->setElementFunctionList($elementFunctions, $append);
        $this->setAttributeFunctionList($attributeFunctions, $append);
    }

    public function addAttributeFunction($name_1, $function_1, $name_n = null, $function_n = null)
    {
        $args = MUtil_Ra::pairs(func_get_args());

        return $this->setAttributeFunctionList($args, true);
    }

    public function addElementFunction($name_1, $function_1, $name_n = null, $function_n = null)
    {
        $args = MUtil_Ra::pairs(func_get_args());

        $this->setElementFunctionList($args, true);

        return $this;
    }

    public function create($tagName, array $args = array())
    {
        if ($function = $this->_elementFunctionList->get($tagName)) {
            return call_user_func_array($function, $args);

        } else {
            return new MUtil_Html_HtmlElement($tagName, $args);
        }
    }

    public function createAttribute($attributeName, array $args = array())
    {
        if ($function = $this->_attributeFunctionList->get($attributeName)) {
            return call_user_func($function, $args);

        } else {
            return new MUtil_Html_ArrayAttribute($attributeName, $args);

        }
    }

    public function createRaw($tagName, array $args = array())
    {
        return new MUtil_Html_HtmlElement($tagName, $args);
    }

    public function getAttributeFunctionList()
    {
        return $this->_attributeFunctionList;
    }

    public function getElementFunctionList()
    {
        return $this->_elementFunctionList;
    }

    public function setAttributeFunctionList($attributeFunctions, $append = false)
    {
        if ($attributeFunctions instanceof MUtil_Util_LookupList) {
            $this->_attributeFunctionList = $attributeFunctions;
        } else {
            $this->_attributeFunctionList = new MUtil_Util_FunctionList($this->_initialAttributeFunctions);

            if ($attributeFunctions) {
                if ($append) {
                    $this->_attributeFunctionList->add((array) $attributeFunctions);
                } else {
                    $this->_attributeFunctionList->set((array) $attributeFunctions);
                }
            }
        }
        return $this;
    }

    public function setElementFunctionList($elementFunctions, $append = false)
    {
        if ($elementFunctions instanceof MUtil_Util_LookupList) {
            $this->_elementFunctionList = $elementFunctions;
        } else {
            if (! $this->_elementFunctionList instanceof MUtil_Util_FunctionList) {
                $this->_elementFunctionList = new MUtil_Util_FunctionList($this->_initalElementFunctions);
            }

            if ($elementFunctions) {
                if ($append) {
                    $this->_elementFunctionList->add((array) $elementFunctions);
                } else {
                    $this->_elementFunctionList->set((array) $elementFunctions);
                }
            }
        }
        return $this;
    }
}
