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
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This controller handles applying upgrades to the project
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Default_UpgradeAction extends \Gems_Controller_Action
{
    /**
     * @var \Gems_Upgrades
     */
    protected $_upgrades;

    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Gems_Menu
     */
    public $menu;

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
    public $useHtmlView = true;

    /**
     * Show a compatibility report
     */
    public function compatibilityReportAction()
    {
        $this->addSnippet('Upgrade\\UpgradeCompatibilitySnippet', 'escort', $this->escort);
    }

    public function init()
    {
        parent::init();

        $this->_upgrades = $this->loader->getUpgrades();

    }

    /**
     * Executes the upgrades for a certain context
     *
     * optional: give from and to levels
     *
     * usage: execute/context/<context>{/from/int/to/int}
     */
    protected function executeAction($from = null, $to = null)
    {
        $context = $this->getRequest()->getParam('id', 'gems');
        $from    = $this->getRequest()->getParam('from', $from);
        $to      = $this->getRequest()->getParam('to', $to);

        $batch = $this->loader->getTaskRunnerBatch('upgrade' . $context);
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (!$batch->isLoaded()) {
            $this->_upgrades->setBatch($batch);
            $this->_upgrades->execute($context, $to, $from);
        }

        $title = sprintf($this->_('Upgrading %s'), $context);
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->html->br();
        $this->compatibilityReportAction();
    }

    /**
     * Proxy for the menu
     */
    public function executeAllAction()
    {
        $this->executeAction();
    }

    /**
     * Proxy for the menu
     */
    public function executeFromAction()
    {
        $this->executeAction();
    }

    /**
     * Proxy for the menu
     */
    public function executeLastAction()
    {
        $level = $this->_upgrades->getmaxLevel();

        $this->executeAction($level, $level);
    }

    /**
     * Proxy for the menu
     */
    public function executeOneAction()
    {
        $this->executeAction();
    }

    /**
     * Proxy for the menu
     */
    public function executeToAction()
    {
        $this->executeAction();
    }

    /**
     * Overview of available contexts, max upgrade level and achieved upgrade level
     */
    public function indexAction()
    {
        $this->html->h3($this->getTopicTitle());

        $displayColumns = array('link'     => '',
                                'context'  => $this->_('Context'),
                                'maxLevel' => $this->_('Max level'),
                                'level'    => $this->_('Level'));

        foreach($this->_upgrades->getUpgradesInfo() as $row) {
            $menuItem = $this->menu->findAllowedController($this->_getParam('controller'), 'show');
            if ($menuItem) {
                $row['link'] = $menuItem->toActionLinkLower($this->getRequest(), $row);
            }
            $data[] = $row;
        }
        $this->addSnippet('SelectiveTableSnippet', 'data', $data, 'class', 'browser table', 'columns', $displayColumns);
    }

    /**
     * Show the upgrades and level for a certain context
     *
     * Usage: show/context/<context>
     */
    public function showAction()
    {
        $this->html->h3($this->getTopicTitle());

        $context = $this->_getParam('id', 'gems');
        $this->_upgrades->setContext($context);
        if ($info = $this->_upgrades->getUpgradesInfo($context)) {
            $this->html->table(array('class'=>'browser'))->tr()
                ->th($this->_('Context'))->td($info['context'])
                ->tr()
                ->th($this->_('Level'))->td($info['level']);
            $data = $this->_upgrades->getUpgrades();
            foreach($data as $level => $row) {
                foreach($this->menu->getCurrent()->getChildren() as $menuItem) {
                    if ($menuItem->is('allowed', true)) {
                        $show = true;
                        if ($level <= $info['level'] && $menuItem->is('action','execute-to')) {
                            //When this level is < current level don't allow to execute from current level to this one
                            $show = false;
                        }
                        if ($level <= $info['level'] && $menuItem->is('action','execute-from')) {
                            //When this level is < current level don't allow to execute from current level to this one
                            $show = false;
                        }
                        if ($show) {
                            $row['action'][] = $menuItem->toActionLinkLower($this->getRequest(), $row, array('from'=>$level, 'to'=>$level));
                        }
                    }
                }
                $row['level'] = $level;
                $data[$level] = $row;
            }
            $displayColumns = array('level' => $this->_('Level'),
                                    'info'   => $this->_('Description'),
                                    'action' => $this->_('Action'));
            $this->addSnippet('SelectiveTableSnippet', 'data', $data, 'class', 'browser', 'columns', $displayColumns);
        } else {
            $this->html[] = sprintf($this->_('Context %s not found!'), $context);
        }

        if ($parentItem = $this->menu->getCurrent()->getParent()) {
            $this->html[] = $parentItem->toActionLink($this->getRequest(), $this->_('Cancel'));
        }
    }

    public function getTopicTitle() {
        return $this->_('Upgrades');
    }

    public function getTopic($n = 1) {
        return $this->_('Upgrades');
    }
}