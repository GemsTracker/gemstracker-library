<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Form\Element
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Form\Element;

use DateTimeInterface;
use MUtil\Bootstrap\Form\Element\Text;

/**
 *
 * @package    Gems
 * @subpackage Form\Element
 * @since      Class available since version 1.9.2
 */
class DateTimeInput extends Text
{
    protected $_elementClass = 'form-control date-picker';

    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'formDateTime';

    public function getDecorators()
    {
        if ($this->_value instanceof DateTimeInterface && isset($this->dateFormat)) {
            $this->_value = $this->_value->format($this->dateFormat);
        }

        if (isset($this->datePickerSettings)) {
            $this->setAttrib('data-date-picker-settings', json_encode($this->datePickerSettings));
            unset($this->datePickerSettings);
        }

        $this->setAttrib('data-date-format', $this->dateFormat);
        $this->setAttrib('autocomplete', 'off');

        return parent::getDecorators();
    }
}