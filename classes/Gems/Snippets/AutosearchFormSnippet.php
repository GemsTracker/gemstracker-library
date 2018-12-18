<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Display a search form that selects on typed text only
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class Gems_Snippets_AutosearchFormSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * Field name for period filters
     */
    const PERIOD_DATE_USED = 'dateused';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var string The id of a div that contains target that should be replaced.
     */
    protected $containingId;

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = array();

    /**
     * Optional string format for date
     *
     * @var string
     */
    protected $dateFormat;

    /**
     *
     * @var \Gems_Form
     */
    protected $form;

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Should the organization element be displayed as a multicheckbox or not?
     *
     * @var boolean
     */
    protected $orgIsMultiCheckbox = true;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Optional, otherwise created from $util
     *
     * @var \Gems_Util_RequestCache
     */
    public $requestCache;

    /**
     *
     * @var array The input data for the model
     */
    protected $searchData = false;

    /**
     *
     * @var \Gems_Util
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
            $fromLabel = reset($dates);
            $dates = key($dates);
        } else {
            $fromLabel = $this->_('From');
        }
        if (is_string($dates)) {
            $element = new \Zend_Form_Element_Hidden(self::PERIOD_DATE_USED);
            $element->setValue($dates);
        } else {
            if (count($dates) >= $switchToSelect) {
                $element = $this->_createSelectElement(self::PERIOD_DATE_USED, $dates);
                $element->setLabel($this->_('For date'));

                $fromLabel = '';
            } else {
                $element = $this->_createRadioElement(self::PERIOD_DATE_USED, $dates);
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
        $elements[self::PERIOD_DATE_USED] = $element;

        $type = 'date';
        if ($this->dateFormat) {
            $options['dateFormat'] = $this->dateFormat;
            list($dateFormat, $separator, $timeFormat) = \MUtil_Date_Format::splitDateTimeFormat($options['dateFormat']);

            if ($timeFormat) {
                if ($dateFormat) {
                    $type = 'datetime';
                } else {
                    $type = 'time';
                }
            }
        }
        $options['label'] = $fromLabel;
        \MUtil_Model_Bridge_FormBridge::applyFixedOptions($type, $options);

        $elements['datefrom'] = new \Gems_JQuery_Form_Element_DatePicker('datefrom', $options);

        $options['label'] = ' ' . $this->_('until');
        $elements['dateuntil'] = new \Gems_JQuery_Form_Element_DatePicker('dateuntil', $options);
    }

    /**
     * Creates a \Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string $name  Name of the element
     * @param string $label Label for element
     * @param string $description Optional description
     * @return \Zend_Form_Element_Checkbox
     */
    protected function _createCheckboxElement($name, $label, $description = null)
    {
        if ($name && $label) {
            $element = $this->form->createElement('checkbox', $name);
            $element->setLabel($label);
            $element->getDecorator('Label')->setOption('placement', \Zend_Form_Decorator_Abstract::APPEND);

            if ($description) {
                $element->setDescription($description);
                $element->setAttrib('title', $description);
            }

            return $element;
        }
    }

    /**
     * Creates a \Zend_Form_Element_MultiCheckbox
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param mixed         $separator   Optional separator string
     * @param string        $toggleLabel Optional text for toggle all button, when false no button is added
     * @param boolean       $breakBeforeToggle Enter a newline before the toggle button
     * @return array Of [\Zend_Form_Element_MultiCheckbox, [\MUtil_Bootstrap_Form_Element_ToggleCheckboxes]]
     */
    protected function _createMultiCheckBoxElements($name, $options, $separator = null, $toggleLabel = null, $breakBeforeToggle = false)
    {
        $elements[$name] = $this->_createMultiElement('multiCheckbox', $name, $options, null);

        if (! $elements[$name]) {
            return [];
        }

        if (null === $separator) {
            $separator = ' ';
        }
        $elements[$name]->setSeparator($separator);

        if (false === $toggleLabel) {
            return $elements;
        }

        if ($breakBeforeToggle) {
            $elements['break_' . $name] = \MUtil_Html::create('br');
        }

        $tName = 'toggle_' . $name;
        $options = [
            'label'    => $toggleLabel ? $toggleLabel : $this->_('Toggle'),
            'selector' => "input[name^=$name]",
            ];
        $elements[$tName] = $this->form->createElement('ToggleCheckboxes', $tName, $options);

        return $elements;
    }

    /**
     * Creates a \Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string        $class   Name of the class to use
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return \Zend_Form_Element_Multi
     */
    private function _createMultiElement($class, $name, $options, $empty)
    {
        if ($options instanceof \MUtil_Model_ModelAbstract) {
            $options = $options->get($name, 'multiOptions');
        } elseif (is_string($options)) {
            $options = $this->db->fetchPairs($options);
            natsort($options);
        }
        if ($options || null !== $empty)
        {
            if (null !== $empty) {
                $options = array('' => $empty) + (array) $options;
            }
            $element = $this->form->createElement($class, $name, array('multiOptions' => $options));

            return $element;
        }
    }

    /**
     * Creates a \Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return \Zend_Form_Element_Radio
     */
    protected function _createRadioElement($name, $options, $empty = null)
    {
        return $this->_createMultiElement('radio', $name, $options, $empty);
    }

    /**
     * Creates a \Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string        $name    Name of the select element
     * @param string|array  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return \Zend_Form_Element_Select
     */
    protected function _createSelectElement($name, $options, $empty = null)
    {
        return $this->_createMultiElement('select', $name, $options, $empty);
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

        /*if ($this->util && (false !== $this->searchData) && (! $this->requestCache)) {
            $this->requestCache = $this->util->getRequestCache();
        }
        if ($this->requestCache) {
            // Do not store searchButtonId
            $this->requestCache->removeParams($this->searchButtonId);
        }*/
    }

    /**
     * Creates the form itself
     *
     * @param array $options
     * @return \Gems_Form
     */
    protected function createForm($options = null)
    {
        $form = new \Gems_Form($options);

        return $form;
    }

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of (possible nested) \Zend_Form_Element's or static text to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        // Search text
        $element = $this->form->createElement('text', $this->model->getTextFilter(), array('label' => $this->_('Free search text'), 'size' => 20, 'maxlength' => 30));
        return array($element);
    }

    /**
     * Creates an autosearch form for indexAction.
     *
     * @return \Gems_Form|null
     */
    protected function getAutoSearchForm()
    {
        $data = $this->getSearchData();
        // \MUtil_Echo::track($data);

        $this->form = $form = $this->createForm(array('name' => 'autosubmit', 'class' => 'form-inline', 'role' => 'form'));

        $elements = $this->getAutoSearchElements($data);

        if ($elements) {
            // Assign a name so autosubmit will only work on this form (when there are others)
            $form->setHtml('div');
            $div = $form->getHtml();
            $div->class = 'search';

            $span = $div->div(array('class' => 'panel panel-default'))->div(array('class' => 'inputgroup panel-body'));

            $elements[] = $this->getAutoSearchSubmit();

            if ($reset = $this->getAutoSearchReset()) {
                $elements[] = $reset;
            }

            $prev = null;
            foreach (\MUtil_Ra::flatten($elements) as $element) {
                if ($element instanceof \Zend_Form_Element) {
                    $appendLabel = false;
                    if ($element->getLabel()) {
                        $labelDecor = $element->getDecorator('Label');

                        if ($labelDecor) {
                            $appendLabel = \Zend_Form_Decorator_Abstract::APPEND === $labelDecor->getPlacement();

                            if (! $appendLabel) {
                                $span->label($element);
                            }
                        }
                    }
                    $span->input($element);
                    if ($appendLabel) {
                        $span->label($element);
                    }
                    // TODO: Elementen automatisch toevoegen in \MUtil_Form
                    $form->addElement($element);
                } elseif (null === $element && $prev !== null) {
                    $span = $div->div(array('class' => 'panel panel-default'))->div(array('class' => 'inputgroup panel-body'));
                } else {
                    $span[] = $element;
                }
                $prev = $element;
            }

            // \MUtil_Echo::track($data);
            if ($this->request->isPost()) {
                if (! $form->isValid($data)) {
                    $this->addMessage($form->getErrorMessages());
                    $this->addMessage($form->getMessages());
                }
            } else {
                $form->populate($data);
            }

            if ($this->containingId) {
                $href = $this->getAutoSearchHref();
                if ($href) {
                    $form->setAutoSubmit($href, $this->containingId);
                }
            }

            return $form;
        }
    }

    /**
     *
     * @return string Href attribute for type as you go autofilter
     */
    protected function getAutoSearchHref()
    {
        // We should add hidden parameters to the url
        $neededParams = $this->getFixedParams();
        $searchData   = $this->getSearchData();
        $fixedParams  = array_intersect_key($searchData, array_flip($neededParams));
        $href = array('action' => 'autofilter', $this->model->getTextFilter() => null, 'RouteReset' => true) + $fixedParams;
        return \MUtil_Html::attrib('href', $href);
    }

    /**
     * Creates a reset button for the search form
     *
     * @return \Zend_Form_Element_Html or null
     */
    protected function getAutoSearchReset()
    {
        /*if ($menuItem = $this->menu->getCurrent()) {
            $link    = $menuItem->toActionLink($this->request, array('reset' => 1), $this->_('Reset search'));

            $element = new \MUtil_Form_Element_Html('reset');
            $element->setValue($link);

            return $element;
        }*/
    }

    /**
     * Creates a submit button
     *
     * @return \Zend_Form_Element_Submit
     */
    protected function getAutoSearchSubmit()
    {
        return $this->form->createElement('submit', $this->searchButtonId, array('label' => $this->_('Search'), 'class' => 'button small'));
    }

    /**
     * Return the fixed parameters
     *
     * Normally these are the hidden parameters like ID
     *
     * @return array
     */
    protected function getFixedParams()
    {
        return [];
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        return $this->getAutoSearchForm();
    }

    /**
     * Helper function to generate a period query string
     *
     * @param array $filter A filter array or $request->getParams()
     * @param \Zend_Db_Adapter_Abstract $db
     * @param $inFormat Optional format to use for date when reading
     * @param $outFormat Optional format to use for date in query
     * @return string
     */
    public static function getPeriodFilter(array &$filter, \Zend_Db_Adapter_Abstract $db, $inFormat = null, $outFormat = null)
    {
        $from   = array_key_exists('datefrom', $filter) ? $filter['datefrom'] : null;
        $until  = array_key_exists('dateuntil', $filter) ? $filter['dateuntil'] : null;
        $period = array_key_exists(self::PERIOD_DATE_USED, $filter) ? $filter[self::PERIOD_DATE_USED] : null;

        unset($filter[self::PERIOD_DATE_USED], $filter['datefrom'], $filter['dateuntil']);

        if (! $period) {
            return;
        }

        if (null === $outFormat) {
            $outFormat = 'yyyy-MM-dd';
        }
        if (null === $inFormat) {
            $inFormat  = \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');
        }

        if ($from && \Zend_Date::isDate($from,  $inFormat)) {
            $datefrom = $db->quote(\MUtil_Date::format($from, $outFormat, $inFormat));
        } else {
            $datefrom = null;
        }
        if ($until && \Zend_Date::isDate($until,  $inFormat)) {
            $dateuntil = $db->quote(\MUtil_Date::format($until, $outFormat, $inFormat));
        } else {
            $dateuntil = null;
        }

        if (! ($datefrom || $dateuntil)) {
            return;
        }

        switch ($period[0]) {
            case '_':
                // overlaps
                $periods = explode(' ', substr($period, 1));

                if ($datefrom && $dateuntil) {
                    return sprintf(
                            '(%1$s <= %4$s OR (%1$s IS NULL AND %2$s IS NOT NULL)) AND
                                (%2$s >= %3$s OR %2$s IS NULL)',
                            $db->quoteIdentifier($periods[0]),
                            $db->quoteIdentifier($periods[1]),
                            $datefrom,
                            $dateuntil
                            );
                }
                if ($datefrom) {
                    return sprintf(
                            '%2$s >= %3$s OR (%2$s IS NULL AND %1$s IS NOT NULL)',
                            $db->quoteIdentifier($periods[0]),
                            $db->quoteIdentifier($periods[1]),
                            $datefrom
                            );
                }
                if ($dateuntil) {
                    return sprintf(
                            '%1$s <= %3$s OR (%1$s IS NULL AND %2$s IS NOT NULL)',
                            $db->quoteIdentifier($periods[0]),
                            $db->quoteIdentifier($periods[1]),
                            $dateuntil
                            );
                }
                return;

            case '-':
                // within
                $periods = explode(' ', substr($period, 1));

                if ($datefrom && $dateuntil) {
                    return sprintf(
                            '%1$s >= %3$s AND %2$s <= %4$s',
                            $db->quoteIdentifier($periods[0]),
                            $db->quoteIdentifier($periods[1]),
                            $datefrom,
                            $dateuntil
                            );
                }
                if ($datefrom) {
                    return sprintf(
                            '%1$s >= %3$s AND (%2$s IS NULL OR %2$s >= %3$s)',
                            $db->quoteIdentifier($periods[0]),
                            $db->quoteIdentifier($periods[1]),
                            $datefrom
                            );
                }
                if ($dateuntil) {
                    return sprintf(
                            '%2$s <= %3$s AND (%1$s IS NULL OR %1$s <= %3$s)',
                            $db->quoteIdentifier($periods[0]),
                            $db->quoteIdentifier($periods[1]),
                            $dateuntil
                            );
                }
                return;

            default:
                if ($datefrom && $dateuntil) {
                    return sprintf(
                            '%s BETWEEN %s AND %s',
                            $db->quoteIdentifier($period),
                            $datefrom,
                            $dateuntil
                            );
                }
                if ($datefrom) {
                    return sprintf(
                            '%s >= %s',
                            $db->quoteIdentifier($period),
                            $datefrom
                            );
                }
                if ($dateuntil) {
                    return sprintf(
                            '%s <= %s',
                            $db->quoteIdentifier($period),
                            $dateuntil
                            );
                }
                return;
        }
    }

    /**
     *
     * @return array The data to fill the form with
     */
    protected function getSearchData()
    {
        if (false !== $this->searchData) {
            // \MUtil_Echo::track($this->searchData);
            return $this->searchData;
        }
        if ($this->requestCache) {
            $filter = $this->requestCache->getProgramParams();
        } else {
            $filter = $this->request->getParams();
        }

        if ($this->defaultSearchData) {
            $filter = $filter + $this->defaultSearchData;
        }

        // \MUtil_Echo::track($this->searchData, $filter);
        // return $this->searchData;
        return $data;
    }
}
