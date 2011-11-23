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
 * Adds Gems specific display details and helper functions:
 *
 * Items set are:
 * - Display class: 'browser'
 *
 * Extra helpers are:
 * - Baseurl helper:  $this->requestCache
 * - Keyboard access: $this->keyboard & getHtmlOutput()
 * - Menu helpers:    $this->menu, findMenuItem()
 * - Sort parameters: $sortParamAsc & $sortParamDesc
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class Gems_Snippets_ModelTableSnippetAbstract extends MUtil_Snippets_ModelTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser';

    /**
     *
     * @var string The id of a div that contains the table.
     */
    protected $containingId;

    /**
     * Use keyboard to select row
     *
     * @var boolean
     */
    public $keyboard = false;

    /**
     *
     * @var Gems_Menu
     */
    public $menu;

    /**
     * Optional, used for base url
     *
     * @var Gems_Util_RequestCache
     */
    public $requestCache;

    /**
     * The $request param that stores the ascending sort
     *
     * @var string
     */
    protected $sortParamAsc = 'asrt';

    /**
     * The $request param that stores the descending sort
     *
     * @var string
     */
    protected $sortParamDesc = 'dsrt';

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        if ($showMenuItem = $this->getShowMenuItem()) {
            $bridge->addItemLink($showMenuItem->toActionLinkLower($this->request, $bridge));
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        parent::addBrowseTableColumns($bridge, $model);

        if ($editMenuItem = $this->getEditMenuItem()) {
            $bridge->addItemLink($editMenuItem->toActionLinkLower($this->request, $bridge));
        }
    }

    /**
     * Add the paginator panel to the table.
     *
     * Only called when $this->browse is true. Overrule this function
     * to define your own method.
     *
     * $param Zend_Paginator $paginator
     */
    protected function addPaginator(MUtil_Html_TableElement $table, Zend_Paginator $paginator)
    {
        $table->tfrow()->pagePanel($paginator, $this->request, $this->translate, array('baseUrl' => $this->baseUrl));
    }

    /**
     * Make sure generic search text results are marked
     *
     * @return void
     */
    protected function applyTextMarker()
    {
        $model = $this->getModel();

        $textKey = $model->getTextFilter();
        $filter  = $model->getFilter();

        if (isset($filter[$textKey])) {
            $searchText = $filter[$textKey];
            // MUtil_Echo::r('[' . $searchText . ']');
            $marker = new MUtil_Html_Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
            foreach ($model->getItemNames() as $name) {
                if ($model->get($name, 'label')) {
                    $model->set($name, 'markCallback', array($marker, 'mark'));
                }
            }
        }
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->requestCache) {
            // Items that should not be stored.
            $this->requestCache->removeParams('page', 'items', 'action');

            if ((! $this->baseUrl)) {
                $this->baseUrl = $this->requestCache->getProgramParams();

                if (MUtil_Registry_Source::$verbose) {
                    MUtil_Echo::track($this->baseUrl);
                }
            }
        }

        return parent::checkRegistryRequestsAnswers();
    }

    /**
     *
     * @param mixed $parameterSource
     * @param string $controller
     * @param string $action
     * @param string $label
     * @return MUtil_Html_AElement
     */
    public function createMenuLink($parameterSource, $controller, $action = 'index', $label = null)
    {
        if ($menuItem  = $this->findMenuItem($controller, $action)) {
            return $menuItem->toActionLinkLower($this->request, $parameterSource, $label);
        }
    }

    /**
     * Finds a specific active menu item
     *
     * @param string $controller
     * @param string $action
     * @return Gems_Menu_SubMenuItem
     */
    protected function findMenuItem($controller, $action = 'index')
    {
        return $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return Gems_Menu_SubMenuItem
     */
    protected function getEditMenuItem()
    {
        return $this->findMenuItem($this->request->getControllerName(), 'edit');
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
        $table = parent::getHtmlOutput($view);
        $table->getOnEmpty()->class = 'centerAlign';

        if ($this->containingId || $this->keyboard) {
            $this->applyHtmlAttributes($table);

            $div = MUtil_Html::create()->div(array('id' => $this->containingId ? $this->containingId : 'keys_target'), $table);

            if ($this->keyboard) {
                return array($div, new Gems_JQuery_TableRowKeySelector($div));
            } else {
                return $div;
            }
        } else {
            return $table;
        }
    }

    /**
     * Returns a show menu item, if access is allowed by privileges
     *
     * @return Gems_Menu_SubMenuItem
     */
    protected function getShowMenuItem()
    {
        return $this->findMenuItem($this->request->getControllerName(), 'show');
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        if ($this->requestCache) {
            $data = $this->requestCache->getProgramParams();

            $model->applyParameters($data);

        } else {
            parent::processFilterAndSort($model);
        }
    }
}
