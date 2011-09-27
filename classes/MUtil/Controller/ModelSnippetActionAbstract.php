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
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * Controller class with standard model and snippet based browse (index), IN THE NEAR FUTURE show, edit and delete actions.
 *
 * To change the behaviour of this class the prime method is changing the snippets used for an action.
 *
 * @package    MUtil
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.2
 */
abstract class MUtil_Controller_ModelSnippetActionAbstract extends MUtil_Controller_ModelActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array();

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'ModelTableSnippet';

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $indexParameters = array();

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = null;

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = null;

    /**
     * The parameters used for the show action
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = array();

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = 'ModelVerticalTableSnippet';

    /**
     * Set the action key in request
     *
     * Use this when an action is a Ajax action for retrieving
     * information for use within the screen of another action
     *
     * @param string $alias
     */
    protected function aliasAction($alias)
    {
        $request = $this->getRequest();
        $request->setActionName($alias);
        $request->setParam($request->getActionKey(), $alias);
    }

    /**
     * The automatically filtered result
     *
     * @param $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        // MUtil_Model::$verbose = true;

        // We do not need to return the layout, just the above table
        if ($resetMvc) {
            // Make sure all links are generated as if the current request was index.
            $this->aliasAction('index');

            Zend_Layout::resetMvcInstance();
        }

        if ($this->autofilterSnippets) {
            $this->autofilterParameters['model']   = $this->getModel();
            $this->autofilterParameters['request'] = $this->getRequest();

            $this->addSnippets($this->autofilterSnippets, $this->autofilterParameters);
        }

        if ($resetMvc && MUtil_Echo::hasOutput()) {
            $this->html->raw(MUtil_Echo::out());
        }
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        if ($this->indexStartSnippets || $this->indexStopSnippets) {
            $this->indexParameters = $this->indexParameters + $this->autofilterParameters;

            $this->indexParameters['model']   = $this->getModel();
            $this->indexParameters['request'] = $this->getRequest();

            if ($this->indexStartSnippets) {
                $this->addSnippets($this->indexStartSnippets, $this->indexParameters);
            }
        }

        $this->autofilterAction(false);

        if ($this->indexStopSnippets) {
            $this->addSnippets($this->indexStopSnippets, $this->indexParameters);
        }
    }


    /**
     * Action for showing an item page
     */
    public function showAction()
    {
        if ($this->showSnippets) {
            $this->showParameters['model']   = $this->getModel();
            $this->showParameters['request'] = $this->getRequest();

            $this->addSnippets($this->showSnippets, $this->showParameters);
        }
    }
}
