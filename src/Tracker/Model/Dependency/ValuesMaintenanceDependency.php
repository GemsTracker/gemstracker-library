<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Tracker\Field\FieldAbstract;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Sequence;
use Zalt\Model\Dependency\DependencyAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 18-mrt-2015 14:00:41
 */
class ValuesMaintenanceDependency extends DependencyAbstract
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected array $_dependentOn = ['gtf_field_type', 'gtf_field_value_keys', 'gtf_field_values'];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected array $_effecteds = [
        'gtf_field_value_keys' => [
            'description', 'elementClass', 'formatFunction', 'label', 'minlength', 'rows', 'required',
        ],
        'gtf_field_values' => [
            'description', 'elementClass', 'formatFunction', 'label', 'minlength', 'rows', 'required',
        ],
        'gtf_field_default' => [
            'description', 'elementClass', 'label', 'multiOptions',
        ],
    ];

    public function __construct(
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
    )
    {
        parent::__construct($translate);
    }

    /**
     * Combine arrays even when the keys are not equal
     *
     * @param array $keys
     * @param array $values
     * @return array
     */
    public static function combineKeyValues(array $keys, array $values)
    {
        $kCount = count($keys);
        $vCount = count($values);

        if ($kCount > $vCount) {
            for ($i = $vCount; $i < $kCount; $i++) {
                $values[$i] = $keys[$i];
            }
        } elseif ($vCount > $kCount) {
            array_splice($values, $kCount);
        }

        return array_combine($keys, $values);
    }

    /**
     * Put each value on a separate line
     *
     * @param string $values
     * @return Sequence
     */
    public function formatLabels($values)
    {
        return new Sequence(['glue' => '<br/>'], explode('|', (string)$values));
    }

    /**
     * Put each value on a separate line
     *
     * @param string $values
     * @return Sequence
     */
    public function formatValues($values)
    {
        return new Sequence(['glue' => '<br/>'], explode('|', (string)$values));
    }

    /**
     * Returns the changes that must be made in an array consisting of
     *
     * <code>
     * array(
     *  field1 => array(setting1 => $value1, setting2 => $value2, ...),
     *  field2 => array(setting3 => $value3, setting4 => $value4, ...),
     * </code>
     *
     * By using [] array notation in the setting name you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array name => array(setting => value)
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $multi = explode(FieldAbstract::FIELD_SEP, $context['gtf_field_values']);

        return [
            'gtf_field_value_keys' => array(
                'label'          => $this->_('Values'),
                'description'    => $this->_('Separate multiple values with a vertical bar (|)'),
                'elementClass'   => 'Textarea',
                'formatFunction' => array($this, 'formatValues'),
                'minlength'      => 3,// At least two single chars and a separator
                'rows'           => 4,
                'required'       => true,
            ),
            'gtf_field_values' => array(
                'label'          => $this->_('Labels'),
                'description'    => $this->_('Separate multiple labels with a vertical bar (|)'),
                'elementClass'   => 'Textarea',
                'formatFunction' => array($this, 'formatLabels'),
                'minlength'      => 3,// At least two single chars and a separator
                'rows'           => 4,
                'required'       => true,
            ),
            'translations_gtf_field_values' => [
                'model' => [
                    'gtrs_translation' => [
                            'elementClass'   => 'Textarea',
                            'formatFunction' => [$this, 'formatValues'],
                            'minlength'      => 3,// At least two single chars and a separator
                            'rows'           => 4,
                    ],
                    'gtrs_iso_lang' => [
                        'elementClass' => 'Exhibitor',
                    ],
                ]
            ],
            'gtf_field_default' => [
                'label'        => $this->_('Default'),
                'description'  => $this->_('Choose the default value'),
                'elementClass' => 'Select',
                'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + array_combine($multi, $multi),
            ],
        ];
    }
}
