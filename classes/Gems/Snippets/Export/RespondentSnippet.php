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
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Show info about the respondent during html/pdf export
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class Gems_Snippets_Export_RespondentSnippet extends MUtil_Snippets_SnippetAbstract
{
    /**
     * The data for the current respondentId
     *
     * @var array
     */
    public $data;

    /**
     * @var Gems_Model_RespondentModel
     */
    public $model;

    /**
     * The respondent we are looking at
     *
     * @var int
     */
    public $respondentId;

    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        parent::getHtmlOutput($view);

        $respondentModel = $this->model;
        $respondentData = $this->data;
        $respondentId = $this->respondentId;

        $html = $this->getHtmlSequence();
        if (empty($this->data)) {
            $html->p()->b(sprintf($this->_('Unknown respondent %s'), $respondentId));
            return $html;
        }

        $bridge = $respondentModel->getBridgeFor('itemTable', array('class' => 'browser table'));
        $bridge->setRepeater(MUtil_Lazy::repeat(array($respondentData)));
        $bridge->th($this->_('Respondent information'), array('colspan' => 4));
        $bridge->setColumnCount(2);
        foreach($respondentModel->getItemsOrdered() as $name) {
            if ($label = $respondentModel->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        $tableContainer = MUtil_Html::create()->div(array('class' => 'table-responsive'));
        $tableContainer[] = $bridge->getTable();

        $html->h2($this->_('Respondent information') . ': ' . $respondentId);
        $html[] = $tableContainer;
        $html->hr();

        return $html;
    }

}