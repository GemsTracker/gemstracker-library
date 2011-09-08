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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: OverviewPlanAction.php 430 2011-08-18 10:40:21Z 175780 $
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_OverviewPlanAction extends Gems_Default_TokenPlanAction
{
    public $tableSnippets = array('TokenDateSelectorSnippet', 'SelectedTokensTitleSnippet');

    /**
     *
     * @var Gems_Selector_DateSelectorAbstract
     */
    public $dateSelector;

    public $sortKey = array();

    public $useKeyboardSelector = false;

    protected function _createTable()
    {
        $this->getDateSelector();

        return parent::_createTable();
    }
    /**
     * Returns overview specific autosearch fields. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements[] = new Zend_Form_Element_Hidden(Gems_Selector_DateSelectorAbstract::DATE_FACTOR);
        $elements[] = new Zend_Form_Element_Hidden(Gems_Selector_DateSelectorAbstract::DATE_GROUP);
        $elements[] = new Zend_Form_Element_Hidden(Gems_Selector_DateSelectorAbstract::DATE_TYPE);

        return array_merge($elements, $this->getAutoSearchSelectElements());
    }

    protected function getDataFilter(array $data)
    {
        // MUtil_Echo::r($data, __FUNCTION__);
        $parent = parent::getDataFilter($data);

        $selector = $this->getDateSelector();
        return array_merge($parent, $selector->getFilter($this->request, $parent + $data));
    }

    /**
     *
     * @return Gems_Selector_DateSelectorAbstract
     */
    public function getDateSelector()
    {
        if (! $this->dateSelector) {
            $this->dateSelector = $this->loader->getSelector()->getTokenDateSelector();
        }

        return $this->dateSelector;
    }

    public function getDefaultSearchData()
    {
        return $this->getDateSelector()->getDefaultSearchData()
                + array('gto_id_organization' => $this->escort->getCurrentOrganization());
    }

    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Planning overview');
    }
}

