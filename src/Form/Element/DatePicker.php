<?php

namespace Gems\Form\Element;

use DateTimeInterface;
use MUtil\Bootstrap\Form\Element\Text;
use Zend_View_Interface;

class DatePicker extends Text
{

    protected $_elementClass = 'form-control date-picker';

    public function render(Zend_View_Interface $view = null)
    {
        if ($this->_value instanceof DateTimeInterface && isset($this->dateFormat)) {
            $this->_value = $this->_value->format($this->dateFormat);
        }

        if (isset($this->datePickerSettings)) {
            $this->setAttrib('data-date-picker-settings', json_encode($this->datePickerSettings));
            unset($this->datePickerSettings);
        }

        $this->setAttrib('data-date-format', $this->dateFormat);
        unset($this->dateFormat);
        unset($this->storageFormat);

        return parent::render($view);
    }
}