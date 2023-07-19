<?php

declare(strict_types=1);


/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Agenda;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\Dependency\ValueSwitchDependency;
use Zalt\Model\MetaModel;

/**
 * Default dependency for any AppointFilter
 *
 * @package    Gems
 * @subpackage Agenda
 * @since      Class available since version 2.0
 */
abstract class FilterModelDependencyAbstract extends ValueSwitchDependency
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected array $_dependentOn = ['gaf_class'];

    /**
     * The number of gaf_filter_textN fields/
     *
     * @var int
     */
    protected int $_fieldCount = 4;

    /**
     * The maximum length of the calculated name
     *
     * @var int
     */
    protected int $_maxNameCalcLength = 200;

    public function __construct(TranslatorInterface $translate)
    {
        parent::__construct([], $translate);
        
        $this->afterLoad();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterLoad(): void
    {
        $setOnSave = MetaModel::SAVE_TRANSFORMER;
        $switches  = $this->getTextSettings();

        // Make sure the calculated name is saved
        if (! isset($switches['gaf_calc_name'], $switches['gaf_calc_name'][$setOnSave])) {
            $switches['gaf_calc_name'][$setOnSave] = array($this, 'calculateAndCheckName');
        }

        // Make sure the class name is always saved
        $className = $this->getFilterClass();
        $switches['gaf_class'][$setOnSave] = $className;

        // Check all the fields
        for ($i = 1; $i <= $this->_fieldCount; $i++) {
            $field = 'gaf_filter_text' . $i;
            if (! isset($switches[$field])) {
                $switches[$field] = array('label' => null, 'elementClass' => 'Hidden');
            }
        }

        $this->addSwitches(array($className => $switches));
    }

    /**
     * A ModelAbstract->setOnSave() function that returns a string describing the filter
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function calculateAndCheckName(mixed $value, bool $isNew = false, string|null $name = null, array $context = []): string
    {
        return substr($this->calculateName($value, $isNew, $name, $context), 0, $this->_maxNameCalcLength);
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
    abstract public function calculateName(mixed $value, bool $isNew = false, string|null $name = null, array $context = []): string;

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    abstract public function getFilterClass(): string;

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    abstract public function getFilterName(): string;

    /**
     * Get the settings for the gaf_filter_textN fields
     *
     * Fields not in this array are not shown in any way
     *
     * @return array gaf_filter_textN => array(modelFieldName => fieldValue)
     */
    abstract public function getTextSettings(): array;

    /**
     * Set the maximum length of the calculated name field
     *
     * @param int $length
     * @return \Gems\Agenda\FilterModelDependencyAbstract
     */
    public function setMaximumCalcLength(int $length = 200): self
    {
        $this->_maxNameCalcLength = $length;

        return $this;
    }
}
