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
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentDetailSnippetAbstract.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 * Prepares displays of respondent information
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class Gems_Snippets_RespondentDetailSnippetAbstract extends Gems_Snippets_MenuSnippetAbstract
{
    /**
     * Optional: array of buttons
     * 
     * @var array
     */
    protected $buttons;
    
    /**
     *
     * @var Gems_Model_RespondentModel
     */
    protected $model;
    
    /**
     * Optional: href for onclick 
     * 
     * @var MUtil_Html_HrefArrayAttribute
     */
    protected $onclick;
    
    /**
     * Optional: repaeter respondentData
     * 
     * @var MUtil_Lazy_RepeatableInterface
     */
    protected $repeater;
    
    /**
     * Optional: not always filled, use repeater
     * 
     * @var array
     */
    protected $respondentData;

    /**
     *
     * @param MUtil_Model_VerticalTableBridge $bridge 
     * @return void
     */
    protected function addButtons(MUtil_Model_VerticalTableBridge $bridge)
    {
        if ($this->buttons) {
            $bridge->tfrow($this->buttons, array('class' => 'centerAlign'));
        }
    }

    /**
     *
     * @param MUtil_Model_VerticalTableBridge $bridge 
     * @return void
     */
    protected function addOnClick(MUtil_Model_VerticalTableBridge $bridge)
    {
        if ($this->onclick) {
            $bridge->tbody()->onclick = array('location.href=\'', $this->onclick, '\';');
        }
    }
    
    /**
     * Place to set the data to display
     * 
     * @param MUtil_Model_VerticalTableBridge $bridge
     * @return void
     */
    abstract protected function addTableCells(MUtil_Model_VerticalTableBridge $bridge);
    
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
        $bridge = new MUtil_Model_VerticalTableBridge($this->model, array('class' => 'displayer'));
        $bridge->setRepeater($this->repeater);
        $bridge->setColumnCount(2); // May be overruled
        
        $this->addTableCells($bridge);
        $this->addButtons($bridge);
        $this->addOnClick($bridge);
        
        return $bridge->getTable();
    }
    
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
        if ($this->model) {
            $this->model->setIfExists('grs_email', 'itemDisplay', 'MUtil_Html_AElement::ifmail');
            $this->model->setIfExists('gr2o_comments', 'rowspan', 2);

            if (! $this->repeater) {
                if (! $this->respondentData) {
                    $this->repeater = $this->model->loadRepeatable();
                } else {
                    // In case a single array of values was passed: make nested
                    if (! is_array(reset($this->respondentData))) {
                        $this->respondentData = array($this->respondentData);
                    }
                    
                    $this->repeater = MUtil_Lazy::repeat($this->respondentData);
                }
            }
            
            return true;
        }
        
        return false;
    }
}
