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
 * Displays each fields of a single item in a model in a row in a Html table.
 *
 * To use this class either subclass or use the existing default ModelVerticalTableSnippet.
 *
 * @see ModelVerticalTableSnippet.
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class MUtil_Snippets_ModelVerticalTableSnippetAbstract extends MUtil_Snippets_ModelSnippetAbstract
{
    /**
     *
     * @var int The number of columns used in the table bridge.
     */
    protected $bridgeColumns = 1;

    /**
     * One of the MUtil_Model_Bridge_BridgeAbstract MODE constants
     * 
     * @var int 
     */
    protected $bridgeMode = MUtil_Model_Bridge_BridgeAbstract::MODE_LAZY;
    
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class;

    /**
     *
     * @var boolean True when only tracked fields should be retrieved by the nodel
     */
    protected $trackUsage = true;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(MUtil_Model_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        if ($model->has('row_class')) {
            // Make sure deactivated rounds are show as deleted
            foreach ($bridge->getTable()->tbody() as $tr) {
                foreach ($tr as $td) {
                    if ('td' === $td->tagName) {
                        $td->appendAttrib('class', $bridge->row_class);
                    }
                }
            }
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
        $model = $this->getModel();
        if ($this->trackUsage) {
            $model->trackUsage();
        }

        $table = $this->getShowTable($model);
        $table->setRepeater($this->getRepeater($model));

        return $table;
    }

    /**
     * Function that allows for overruling the repeater loading.
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Lazy_RepeatableInterface
     */
    public function getRepeater(MUtil_Model_ModelAbstract $model)
    {
        return $model->loadRepeatable();
    }

    /**
     * Creates from the model a MUtil_Html_TableElement that can display multiple items.
     *
     * Allows overruling
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Html_TableElement
     */
    public function getShowTable(MUtil_Model_ModelAbstract $model)
    {
        $bridge = $model->getBridgeFor('itemTable', array('class' => $this->class));
        $bridge->setColumnCount($this->bridgeColumns)
                ->setMode($this->bridgeMode);

        $this->setShowTableHeader($bridge, $model);
        $this->setShowTableFooter($bridge, $model);
        $this->addShowTableRows($bridge, $model);

        return $bridge->getTable();
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(MUtil_Model_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    { }

    /**
     * Set the header of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function setShowTableHeader(MUtil_Model_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    { }
}
