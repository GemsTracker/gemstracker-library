<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Db\ResultFetcher;
use Gems\Form;
use Gems\Form\Element\DateTimeInput;
use Gems\Handlers\GemsHandler;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql\Select;
use MUtil\Model;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsHandler\SnippetHandler;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Display a search form that selects on typed text only
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class AutosearchFormSnippet extends TranslatableSnippetAbstract
{
    /**
     * Field name for period filters
     */
    const PERIOD_DATE_USED = 'dateused';

    /**
     *
     * @var string The id of a div that contains target that should be replaced.
     */
    protected ?string $containingId = null; // 'autofilter_target';

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = [];

    /**
     * Optional string format for date
     *
     * @var string
     */
    protected $dateFormat;

    /**
     *
     * @var \Gems\Form
     */
    protected $form;

    protected bool $isPost = false;

    /**
     *
     * @var DataReaderInterface|MetaModelInterface
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
     * @var array The input data for the model
     */
    protected $searchData = false;

    /**
     *
     * @var string Id for auto search button
     */
    protected $searchButtonId = 'AUTO_SEARCH_TEXT_BUTTON';

    /**
     *
     * @var string
     */
    protected string $searchLabel = '';

    /**
     * @var string Fieldname for text search
     */
    protected string $textSearchField = 'search';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly MetaModelLoader $metaModelLoader,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly StatusMessengerInterface $messenger,
        )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        if (! $this->searchLabel) {
            $this->searchLabel = $this->_('Free search text');
        }
    }

    /**
     * Generate two date selectors and - depending on the number of $dates passed -
     * either a hidden element containing the field name or an radio button or
     * dropdown selector for the type of date to use.
     *
     * @param array $elements Search element array to which the element are added.
     * @param mixed $dates A string fieldName to use or an array of fieldName => Label
     * @param string $defaultDate Optional element, otherwise first is used.
     * @param int $switchToSelect The number of dates where this function should switch to select display
     * @deprecated Inherit from AutosearchPeriodFormSnippet
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

        // $config = $this->metaModelLoader->getModelConfig();
        $options = ['dateFormat' => 'd-m-Y', 'datePickerSettings' => [], 'size' => 10, 'storageFormat' => 'Y-m-d'];
        $elements['datefrom'] = new DateTimeInput('datefrom', $options);

        $options['label'] = ' ' . $this->_('until');
        $elements['dateuntil'] = new DateTimeInput('dateuntil', $options);
    }

    /**
     * Creates a \Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string $name  Name of the element
     * @param string $label Label for element
     * @param string $description Optional description
     * @return \Zend_Form_Element_Checkbox|null
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
        return null;
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
     * @return array Of [\Zend_Form_Element_MultiCheckbox, [\MUtil\Bootstrap\Form\Element\ToggleCheckboxes]]
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
            $elements['break_' . $name] = Html::create('br');
        }

        $tName = 'toggle_' . $name;
        $options = [
            'label'    => $toggleLabel ?: $this->_('Toggle'),
            'selectorname' => $name,
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
     * @param string|array|MetaModelInterface  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return \Zend_Form_Element_Multi
     */
    private function _createMultiElement($class, $name, $options, $empty)
    {
        if ($options instanceof MetaModelInterface) {
            $options = $options->get($name, 'multiOptions');
        } elseif (is_string($options) || $options instanceof Select) {
            $options = $this->resultFetcher->fetchPairs($options);
            if ($options) {
                natsort($options);
            }
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
     * @param string|array|MetaModelInterface  $options Can be a SQL select string or key/value array of options
     * @param string        $empty   Text to display for the empty selector
     * @return \Zend_Form_Element_Select
     */
    protected function _createSelectElement($name, $options, $empty = null)
    {
        return $this->_createMultiElement('select', $name, $options, $empty);
    }

    /**
     * Creates the form itself
     *
     * @param array $options
     * @return Form
     */
    protected function createForm(array $options = [])
    {
        if (! isset($options['name'])) {
            $className = get_class($this);
            $options['name'] = substr($className, strrpos($className, '\\'));
        }

        $form = new Form($options);
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
        if (! $this->textSearchField) {
            return [];
        }
        // Search text
        $element = $this->form->createElement('text', $this->textSearchField,
                array('label' => $this->searchLabel, 'size' => 20, 'maxlength' => 30));
        return array($element);
    }

    /**
     * Creates an autosearch form for indexAction.
     *
     * @return Form|null
     */
    protected function getAutoSearchForm()
    {
        $data = $this->getSearchData();
        // \MUtil\EchoOut\EchoOut::track($data);

        $this->form = $form = $this->createForm(['class' => 'form-inline', 'role' => 'form']);

        $elements = $this->getAutoSearchElements($data);

        if ($elements) {
            // Data could be changed in getAutoSearchElements, so read it again
            $data = $this->getSearchData();

            // Assign a name so autosubmit will only work on this form (when there are others)
            $div = Html::div();
            $div->class = 'search';
            $form->setHtml($div);

            $span = $div->div(array('class' => 'panel panel-default'))->div(array('class' => 'inputgroup panel-body'));

            $elements[] = $this->getAutoSearchSubmit();

            if ($reset = $this->getAutoSearchReset()) {
                $elements[] = $reset;
            }

            $prev = null;
            foreach (Ra::flatten($elements) as $element) {
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
                    // TODO: Elementen automatisch toevoegen in Form
                    $form->addElement($element);
                } elseif (null === $element && $prev !== null) {
                    $span = $div->div(array('class' => 'panel panel-default'))->div(array('class' => 'inputgroup panel-body'));
                } else {
                    $span[] = $element;
                }
                $prev = $element;
            }

            // \MUtil\EchoOut\EchoOut::track($data);
            if ($this->isPost) {
                if (! $form->isValid($data)) {
                    $this->messenger->addMessages($form->getErrorMessages());
                    $this->messenger->addMessages($form->getMessages());
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
     * @return string|null Href attribute for type as you go autofilter
     */
    protected function getAutoSearchHref():? string
    {
        $route = $this->menuSnippetHelper->getRelatedRoute('autofilter');
        if ($route) {
            return $this->menuSnippetHelper->getRouteUrl($route, $this->requestInfo->getParams());
        }
        return null;
    }

    /**
     * Creates a reset button for the search form
     *
     * @return \MUtil\Form\Element\Html or null
     */
    protected function getAutoSearchReset()
    {
        $routeName = $this->requestInfo->getRouteName();
        $params = $this->requestInfo->getRequestMatchedParams();
        $url = [$this->requestInfo->getBasePath(), GemsHandler::AUTOSEARCH_RESET => 1];

        // @phpstan-ignore method.undefined
        $link = Html::create()->actionLink($url, $this->_('Reset search'));

        $element = new \MUtil\Form\Element\Html('reset');
        $element->setValue($link);

        return $element;
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
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $div = Html::div();
        $div->append($this->getAutoSearchForm());

        return $div;
    }

    /**
     * Helper function to generate a period query string
     *
     * @param array $filter A filter array or $request->getParams()
     * @param PlatformInterface $dbPlatform
     * @param $inFormat Optional format to use for date when reading
     * @param $outFormat Optional format to use for date in query
     * @return string
     * @deprecated use PeriodSelectRepository
     */
    public static function getPeriodFilter(array &$filter, PlatformInterface $dbPlatform, $inFormat = null, $outFormat = null)
    {
        $from   = array_key_exists('datefrom', $filter) ? $filter['datefrom'] : null;
        $until  = array_key_exists('dateuntil', $filter) ? $filter['dateuntil'] : null;
        $period = array_key_exists(self::PERIOD_DATE_USED, $filter) ? $filter[self::PERIOD_DATE_USED] : null;

        unset($filter[self::PERIOD_DATE_USED], $filter['datefrom'], $filter['dateuntil']);

        if (! $period) {
            return;
        }

        if (null === $outFormat) {
            $outFormat = Model::getTypeDefault(Model::TYPE_DATE, 'storageFormat');
        }
        if (null === $inFormat) {
            $inFormat  = Model::getTypeDefault(Model::TYPE_DATE, 'dateFormat');
        }

        $datefrom  = Model::reformatDate($from, $inFormat, $outFormat);
        $dateuntil = Model::reformatDate($until, $inFormat, $outFormat);

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
                            $dbPlatform->quoteIdentifier($periods[0]),
                            $dbPlatform->quoteIdentifier($periods[1]),
                            $dbPlatform->quoteValue($datefrom),
                            $dbPlatform->quoteValue($dateuntil)
                            );
                }
                if ($datefrom) {
                    return sprintf(
                            '%2$s >= %3$s OR (%2$s IS NULL AND %1$s IS NOT NULL)',
                            $dbPlatform->quoteIdentifier($periods[0]),
                            $dbPlatform->quoteIdentifier($periods[1]),
                            $dbPlatform->quoteValue($datefrom)
                            );
                }
                if ($dateuntil) {
                    return sprintf(
                            '%1$s <= %3$s OR (%1$s IS NULL AND %2$s IS NOT NULL)',
                            $dbPlatform->quoteIdentifier($periods[0]),
                            $dbPlatform->quoteIdentifier($periods[1]),
                            $dbPlatform->quoteValue($dateuntil)
                            );
                }
                return;

            case '-':
                // within
                $periods = explode(' ', substr($period, 1));

                if ($datefrom && $dateuntil) {
                    return sprintf(
                            '%1$s >= %3$s AND %2$s <= %4$s',
                            $dbPlatform->quoteIdentifier($periods[0]),
                            $dbPlatform->quoteIdentifier($periods[1]),
                            $dbPlatform->quoteValue($datefrom),
                            $dbPlatform->quoteValue($dateuntil)
                            );
                }
                if ($datefrom) {
                    return sprintf(
                            '%1$s >= %3$s AND (%2$s IS NULL OR %2$s >= %3$s)',
                            $dbPlatform->quoteIdentifier($periods[0]),
                            $dbPlatform->quoteIdentifier($periods[1]),
                            $dbPlatform->quoteValue($datefrom)
                            );
                }
                if ($dateuntil) {
                    return sprintf(
                            '%2$s <= %3$s AND (%1$s IS NULL OR %1$s <= %3$s)',
                            $dbPlatform->quoteIdentifier($periods[0]),
                            $dbPlatform->quoteIdentifier($periods[1]),
                            $dbPlatform->quoteValue($dateuntil)
                            );
                }
                return;

            default:
                if ($datefrom && $dateuntil) {
                    return sprintf(
                            '%s BETWEEN %s AND %s',
                            $dbPlatform->quoteIdentifier($period),
                            $dbPlatform->quoteValue($datefrom),
                            $dbPlatform->quoteValue($dateuntil)
                            );
                }
                if ($datefrom) {
                    return sprintf(
                            '%s >= %s',
                            $dbPlatform->quoteIdentifier($period),
                            $dbPlatform->quoteValue($datefrom)
                            );
                }
                if ($dateuntil) {
                    return sprintf(
                            '%s <= %s',
                            $dbPlatform->quoteIdentifier($period),
                            $dbPlatform->quoteValue($dateuntil)
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
            // \MUtil\EchoOut\EchoOut::track($this->searchData);
            return $this->searchData;
        }
        $filter = $this->requestInfo->getRequestPostParams();

        if ($this->defaultSearchData) {
            $filter = $filter + $this->defaultSearchData;
        }

        $this->searchData = $filter;
        return $this->searchData;
    }
}
