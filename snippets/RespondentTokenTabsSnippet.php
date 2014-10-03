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
 * @version    $Id$
 */

/**
 * Respondent token filter tabs
 *
 * Abstract class for quickly creating a tabbed bar, or rather a div that contains a number
 * of links, adding specific classes for display.
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see MUtil_Registry_TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentTokenTabsSnippet extends MUtil_Snippets_TabSnippetAbstract
{
    /**
     * Default href parameter values
     *
     * Clicking a tab always resets the page counter
     *
     * @var array
     */
    protected $href = array('page' => null);

    /**
     * The RESPONDENT model, not the token model
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

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
        $tabs = $this->getTabs();

        if ($tabs && ($this->displaySingleTab || count($tabs) > 1)) {
            // Set the correct parameters
            $this->getCurrentTab($tabs);

            // Let loose
            if (is_array($this->baseUrl)) {
                $this->href = $this->href + $this->baseUrl;
            }

            if (MUtil_Bootstrap::enabled()) {
                $tabRow = MUtil_Html::create()->ul();

                foreach ($tabs as $tabId => $content) {

                    $li = $tabRow->li(array('class' => $this->tabClass));

                    $li->a($this->getParameterKeysFor($tabId) + $this->href, $content);

                    if ($this->currentTab == $tabId) {
                        $li->appendAttrib('class', $this->tabActiveClass);
                    }
                }
            } else {
                $tabRow = MUtil_Html::create()->div();

                foreach ($tabs as $tabId => $content) {

                    $div = $tabRow->div(array('class' => $this->tabClass));

                    $div->a($this->getParameterKeysFor($tabId) + $this->href, $content);

                    if ($this->currentTab == $tabId) {
                        $div->appendAttrib('class', $this->tabActiveClass);
                    }
                }
            }

            return $tabRow;
        } else {
            return null;
        }
    }


    /**
     * Return optionally the single parameter key which should left out for the default value,
     * but is added for all other tabs.
     *
     * @return mixed
     */
    protected function getParameterKey()
    {
        return 'filter';
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs()
    {
        $tabs['default'] = array($this->_('Default'), 'title' => $this->_('To do 2 weeks ahead and done'));
        $tabs['todo']    = $this->_('To do');
        $tabs['done']    = $this->_('Done');
        $tabs['missed']  = $this->_('Missed');
        $tabs['all']     = $this->_('All');

        return $tabs;
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
        $reqFilter = $this->request->getParam('filter');
        switch ($reqFilter) {
            case 'todo':
                //Only actions valid now that are not already done
                $filter[] = 'gto_completion_time IS NULL';
                $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                break;
            case 'done':
                //Only completed actions
                $filter[] = 'gto_completion_time IS NOT NULL';
                break;
            case 'missed':
                //Only missed actions (not filled in, valid until < today)
                $filter[] = 'gto_completion_time IS NULL';
                $filter[] = 'gto_valid_until < CURRENT_TIMESTAMP';
                break;
            case 'all':
                $filter[] = 'gto_valid_from IS NOT NULL';
                break;
            default:
                //2 weeks look ahead, valid from date is set
                $filter[] = 'gto_valid_from IS NOT NULL';
                $filter[] = 'DATEDIFF(gto_valid_from, CURRENT_TIMESTAMP) < 15';
                $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
        }
        $this->model->setMeta('tab_filter', $filter);

        return parent::hasHtmlOutput();
    }
}
