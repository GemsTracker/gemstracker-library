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
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Display a search form that selects on typed text only
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class Gems_Snippets_AutosearchFormSnippet extends MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

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
     * Optional, otherwise created from $util
     *
     * @var Gems_Util_RequestCache
     */
    public $requestCache;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     *
     * @var string Id for auto search button
     */
    protected $searchButtonId = 'AUTO_SEARCH_TEXT_BUTTON';

    /**
     * Generate two date selectors and - depending on the number of $dates passed -
     * either a hidden element containing the field name or an radio button or
     * dropdown selector for the type of date to use.
     *
     * @param array $elements Search element array to which the element are added.
     * @param mixed $dates A string fieldName to use or an array of fieldName => Label
     * @param string $defaultDate Optional element, otherwise first is used.
     * @param int $switchToSelect The number of dates where this function should switch to select display
     */
    protected function _addPeriodSelectors(array &$elements, $dates, $defaultDate = null, $switchToSelect = 4)
    {
        if (is_array($dates) && (1 === count($dates))) {
            reset($dates);
            $dates = key($dates);
        }
        if (is_string($dates)) {
            $element = new Zend_Form_Element_Hidden('dateused');
            $element->setValue($dates);

            $fromLabel = $this->_('From');
        } else {
            if (count($dates) >= $switchToSelect) {
                $element = $this->_createSelectElement('dateused', $dates);
                $element->setLabel($this->_('For date'));

                $fromLabel = '';
            } else {
                $element = $this->_createRadioElement('dateused', $dates);
                $element->setSeparator(' ');

                $fromLabel = html_entity_decode(' &raquo; ',  ENT_QUOTES, 'UTF-8');
            }
            $fromLabel .= $this->_('from');

            if ((null === $defaultDate) || (! isset($dates[$defaultDate]))) {
                // Set value to first key
                reset($dates);
                $defaultDate = key($dates);
            }
            $element->setValue($defaultDate);
        }
        $elements[] = $element;

        $options = array();
        $options['label'] = $fromLabel;
        MUtil_Model_FormBridge::applyFixedOptions('date', $options);
        $elements[] = new Gems_JQuery_Form_Element_DatePicker('datefrom', $options);

        $options['label'] = ' ' . $this->_('until');
        $elements[] = new Gems_JQuery_Form_Element_DatePicker('dateuntil', $options);
    }

    /**
     * Creates a Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string        $class   Name of the class to use
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return Zend_Form_Element_Multi
     */
    private function _createMultiElement($class, $name, $options, $empty)
    {
        if ($options instanceof MUtil_Model_ModelAbstract) {
            $options = $options->get($name, 'multiOptions');
        } elseif (is_string($options)) {
            $options = $this->db->fetchPairs($options);
            natsort($options);
        }
        if ($options || null !== $empty)
        {
            if (null !== $empty) {
                $options = array('' => $empty) + $options;
            }
            $element = new $class($name, array('multiOptions' => $options));

            return $element;
        }
    }

    /**
     * Creates a Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return Zend_Form_Element_Radio
     */
    protected function _createRadioElement($name, $options, $empty = null)
    {
        return $this->_createMultiElement('Zend_Form_Element_Radio', $name, $options, $empty);
    }

    /**
     * Creates a Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return Zend_Form_Element_Select
     */
    protected function _createSelectElement($name, $options, $empty = null)
    {
        return $this->_createMultiElement('Zend_Form_Element_Select', $name, $options, $empty);
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->util && (! $this->requestCache)) {
            $this->requestCache = $this->util->getRequestCache();
        }
        if ($this->requestCache) {
            // Do not store searchButtonId
            $this->requestCache->removeParams($this->searchButtonId);
        }
    }

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
     * Helper function to generate a period query string
     *
     * @param array $data A filter array or $request->getParams()
     * @param Zend_Db_Adapter_Abstract $db
     * @return string
     */
    public static function getPeriodFilter(array $data, Zend_Db_Adapter_Abstract $db)
    {
        if (isset($data['dateused'])) {
            $options = array();
            MUtil_Model_FormBridge::applyFixedOptions('date', $options);

            $outFormat = 'yyyy-MM-dd';
            $inFormat  = isset($options['dateFormat']) ? $options['dateFormat'] : null;

            if (isset($data['datefrom']) && $data['datefrom']) {
                if (isset($data['dateuntil']) && $data['dateuntil']) {
                    return sprintf(
                            '%s BETWEEN %s AND %s',
                            $db->quoteIdentifier($data['dateused']),
                            $db->quote(MUtil_Date::format($data['datefrom'],  $outFormat, $inFormat)),
                            $db->quote(MUtil_Date::format($data['dateuntil'], $outFormat, $inFormat))
                            );
                }
                return sprintf(
                        '%s >= %s',
                        $db->quoteIdentifier($data['dateused']),
                        $db->quote(MUtil_Date::format($data['datefrom'], $outFormat, $inFormat))
                        );
            }
            if (isset($data['dateuntil']) && $data['dateuntil']) {
                return sprintf(
                        '%s <= %s',
                        $db->quoteIdentifier($data['dateused']),
                        $db->quote(MUtil_Date::format($data['dateuntil'], $outFormat, $inFormat))
                        );
            }
        }
    }

    /**
     *
     * @return array The data to fill the form with
     */
    protected function getSearchData()
    {
        if ($this->requestCache) {
            return $this->requestCache->getProgramParams();
        } else {
            return $this->request->getParams();
        }
    }
}
