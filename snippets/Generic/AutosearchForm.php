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
 * Short description of file
 *
 * @package    Gems
 * @subpackage
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * Short description for class
 *
 * Long description for class (if any)...
 *
 * @package    Gems
 * @subpackage
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.2
 */
class Generic_AutosearchForm extends MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var string The id of a div that contains target that should be replaced.
     */
    protected $containingId;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var string Id for auto search button
     */
    protected $searchButtonId = 'AUTO_SEARCH_TEXT_BUTTON';


    /**
     * Creates the form itself
     *
     * @param array $options
     * @return Gems_Form
     */
    protected function createForm($options = null)
    {
        $form = new Gems_Form($options);

        return $form;
    }

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        // Search text
        $element = new Zend_Form_Element_Text($this->model->getTextFilter(), array('label' => $this->_('Free search text'), 'size' => 20, 'maxlength' => 30));

        return array($element);
    }

    /**
     * Creates an autosearch form for indexAction.
     *
     * @return Gems_Form|null
     */
    protected function getAutoSearchForm()
    {
        $data = $this->getSearchData();

        $elements = $this->getAutoSearchElements($data);

        if ($elements) {
            $form = $this->createForm(array('name' => 'autosubmit')); // Assign a name so autosubmit will only work on this form (when there are others)
            $form->setHtml('div');

            $div = $form->getHtml();
            $div->class = 'search';

            $span = $div->div(array('class' => 'inputgroup'));

            $elements[] = $this->getAutoSearchSubmit();

            foreach ($elements as $element) {
                if ($element instanceof Zend_Form_Element) {
                    if ($element->getLabel()) {
                        $span->label($element);
                    }
                    $span->input($element);
                    // TODO: Elementen automatisch toevoegen in MUtil_Form
                    $form->addElement($element);
                } elseif (null === $element) {
                    $span = $div->div(array('class' => 'inputgroup'));
                } else {
                    $span[] = $element;
                }
            }

            if ($this->request->isPost()) {
                if (! $form->isValid($data)) {
                    $this->addMessage($form->getErrorMessages());
                    $this->addMessage($form->getMessages());
                }
            } else {
                $form->populate($data);
            }

            $href = $this->getAutoSearchHref();
            $form->setAutoSubmit($href, $this->containingId);

            return $form;
        }
    }

    /**
     *
     * @return string Href attribute for type as you go autofilter
     */
    protected function getAutoSearchHref()
    {
        return MUtil_Html::attrib('href', array('action' => 'autofilter', $this->model->getTextFilter() => null, 'RouteReset' => true));
    }

    /**
     * Creates a submit button
     *
     * @param MUtil_Form $form
     * @return Zend_Form_Element_Submit
     */
    protected function getAutoSearchSubmit()
    {
        return new Zend_Form_Element_Submit($this->searchButtonId, array('label' => $this->_('Search'), 'class' => 'button small'));
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
        return $this->getAutoSearchForm();
    }

    /**
     *
     * @return array The data to fill the form with
     */
    protected function getSearchData()
    {
        return $this->request->getParams();
    }
}
