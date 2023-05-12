<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\Agenda;
use Gems\Agenda\FilterModelDependencyAbstract;
use Gems\Agenda\SubFilterDependencyInterface;
use Gems\Html;
use Gems\Menu\RouteHelper;
use Gems\Util\Translated;
use MUtil\Validate\NotEqualTo;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Html\AElement;
use Zalt\Html\HrefArrayAttribute;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-okt-2014 16:56:22
 */
class AndModelDependency extends FilterModelDependencyAbstract implements SubFilterDependencyInterface
{
    /**
     *
     * @var array filter_id => label
     */
    protected $_filters;

    public function __construct(
        TranslatorInterface $translate,
        protected Agenda $agenda,
        protected RouteHelper $routeHelper,
        protected Translated $translatedUtil,
    )
    {
        $this->_filters = $this->translatedUtil->getEmptyDropdownArray() + $this->agenda->getFilterList();

        parent::__construct($translate);
    }

    /**
     * A ModelAbstract->setOnSave() function that returns a string describing the filter
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function calcultateName($value, $isNew = false, $name = null, array $context = array()): string
    {
        $output = $this->calcultateNameOutput($value, $isNew, $name, $context);

        if ($output) {
            return ucfirst(implode($this->getGlue(), $output));
        } else {
            return $this->_('empty filter');
        }
    }

    /**
     * A ModelAbstract->setOnSave() function that returns a string desrcibing the filter
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    protected function calcultateNameOutput($value, $isNew, $name, $context): array
    {
        $output = array();

        // Check all the fields
        for ($i = 1; $i <= $this->_fieldCount; $i++) {
            $field = 'gaf_filter_text' . $i;
            if (isset($context[$field], $this->_filters[$context[$field]]) && $context[$field] && $this->_filters[$context[$field]]) {
                $output[] = $this->_filters[$context[$field]];
            }
        }

        return $output;
    }

    /**
     *
     * @staticvar \Gems\Menu\SubMenuItem $showMenuItem
     * @param int $value
     * @return \Zalt\Html\HtmlElement
     */
    public function displayFilterLink($value, $raw)
    {
        if (isset($this->_filters[$raw])) {
            $class = '';
            $text  = $this->_filters[$raw];
        } else {
            $class = 'deleted';
            $text  = $raw . ' ' . $this->_('(inactive filter)');
        }

        if ($raw && $this->routeHelper->hasAccessToRoute('setup.agenda.filter.show')) {
            $route = $this->routeHelper->getRouteUrl('setup.agenda.filter.show', [\MUtil\Model::REQUEST_ID => intval($raw)]);
            if ($route) {
                return AElement::a(
                    new HrefArrayAttribute($route),
                    $text,
                    ['class' => $class]
                );
            }
        }
        
        return Html::create('span', $text, ['class' => $class]);
    }

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    public function getFilterClass(): string
    {
        return 'AndAppointmentFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName(): string
    {
        return $this->_('AND filter combination');
    }

    /**
     * Get the translated glue for the calculated name
     *
     * @return string
     */
    public function getGlue()
    {
        return $this->_(' AND ');
    }

    /**
     * Get the settings for the gaf_filter_textN fields
     *
     * Fields not in this array are not shown in any way
     *
     * @return array gaf_filter_textN => array(modelFieldName => fieldValue)
     */
    public function getTextSettings(): array
    {
        $messages = array(
            'gaf_id' => $this->_('Sub filter may not be the same as this filter.'),
            $this->_('Filters may be chosen only once.')
        );

        return [
            'gaf_filter_text1' => [
                'label'          => $this->_('Filter 1'),
                'elementClass'   => 'Select',
                'formatFunction' => [$this, 'displayFilterLink', true],
                'multiOptions'   => $this->_filters,
                'required'       => true,
                'validator'      => new NotEqualTo('gaf_id', $messages),
            ],
            'gaf_filter_text2' => [
                'label'          => $this->_('Filter 2'),
                'elementClass'   => 'Select',
                'formatFunction' => [$this, 'displayFilterLink', true],
                'multiOptions'   => $this->_filters,
                'required'       => true,
                'validator'      => new NotEqualTo(array('gaf_id', 'gaf_filter_text1'), $messages),
            ],
            'gaf_filter_text3' => [
                'label'          => $this->_('Filter 3'),
                'elementClass'   => 'Select',
                'formatFunction' => [$this, 'displayFilterLink', true],
                'multiOptions'   => $this->_filters,
                'validator'      => new NotEqualTo(
                    ['gaf_id', 'gaf_filter_text1', 'gaf_filter_text2'],
                    $messages
                ),
            ],
            'gaf_filter_text4' => [
                'label'          => $this->_('Filter 4'),
                'elementClass'   => 'Select',
                'formatFunction' => [$this, 'displayFilterLink', true],
                'multiOptions'   => $this->_filters,
                'validator'      => new NotEqualTo(
                    ['gaf_id', 'gaf_filter_text1', 'gaf_filter_text2', 'gaf_filter_text3'],
                    $messages
                ),
            ],
        ];
    }
}
