<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

use Gems\Db\ResultFetcher;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 18-mrt-2015 11:43:04
 */
class RelationField extends FieldAbstract
{
    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected array|null $_dependsOn = ['gr2t_id_user'];

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected array|null $_effecteds = ['multiOptions'];

    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        TranslatorInterface $translator,
        Translated $translatedUtil,
        protected readonly ResultFetcher $resultFetcher,
    ) {
        parent::__construct($trackId, $fieldKey, $fieldDefinition, $translator, $translatedUtil);
    }

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings): void
    {
        $settings['elementClass']   = 'Select';
        $settings['formatFunction'] = [$this, 'showRelation'];
    }

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo(mixed $currentValue, array $fieldData): mixed
    {
        if (! $currentValue || !is_numeric($currentValue)) {
            return $currentValue;
        }

        $select = $this->getSelect();
        $select->where([
            'grr_id' => (int)$currentValue,
        ]);

        $relation = $this->resultFetcher->fetchRow($select);

        if ($relation && isset($relation['name'])) {
            return $relation['name'];
        }

        return $currentValue;
    }

    /**
     * Returns the changes to the model for this field that must be made in an array consisting of
     *
     * <code>
     *  array(setting1 => $value1, setting2 => $value2, ...),
     * </code>
     *
     * By using [] array notation in the setting array key you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array (setting => value)
     */
    public function getDataModelDependencyChanges(array $context, bool $new): array|null
    {
        if ($this->isReadOnly()) {
            return null;
        }

        $select = $this->getSelect();
        $select->where([
            'grr_id_respondent' => $context['gr2t_id_user'],
        ])
            ->order(['grr_type']);

        $empty  = $this->translatedUtil->getEmptyDropdownArray();

        $output['multiOptions'] = $empty + $this->resultFetcher->fetchPairs($select);

        return $output;
    }

    protected function getSelect(): Select
    {
        $select = $this->resultFetcher->getSelect('gems__respondent_relations');
        $select->columns([
           'grr_id',
           'name' => new Expression("
                CASE
                    WHEN gsf_id_user IS NULL THEN CONCAT_WS(' ', grr_type, grr_first_name, grr_last_name)
                    ELSE CONCAT_WS(' ', grr_type, gsf_first_name, gsf_surname_prefix, gsf_last_name)
                END")
        ])
            ->join('gems__staff', 'gsf_id_user = grr_id_staff');

        return $select;
    }

    /**
     * Display a relation as text
     *
     * @param mixed $value
     * @return string
     */
    public function showRelation($value)
    {
        // Display nicer
        $display = $this->calculateFieldInfo($value, []);
        if ($value == $display) {
            $display = '-';
        }

        return $display;
    }
}
