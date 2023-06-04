<?php


namespace Gems\Form\Decorator;

class AddLanguage extends \Zend_Form_Decorator_Abstract
{
    protected $_format = '
    <div class="input-group">
    	%s
    	%s
    </div>';

    protected $_errorFormat = '
    <div class="input-group has-error has-feedback">
        %s
        %s
    </div>';

    protected $_elementClass = 'form-control';

    protected $flagDir = 'gems-responsive/images/locale/png/';

    protected $flagSize = 30;

    protected $flagExtension = '.png';

    protected function getFlag($countryCode)
    {
        $basePath = \MUtil\Controller\Front::getRequest()->getBasePath();

        return $basePath . '/' . $this->flagDir . $countryCode . '-' . $this->flagSize . $this->flagExtension;
    }

    protected function getFlagTag($countryCode)
    {
        $src = $this->getFlag($countryCode);
        return '<img src="' . $src . '" alt="' . $countryCode . '" />';
    }

    public function render($content)
    {
        if (!isset($this->_options['language'])) {
            return $content;
        }
        $element = $this->getElement();
        $inputGroupAddon = sprintf('<div class="input-group-addon">%s</div>', $this->getFlagTag($this->_options['language']));
        if($element->hasErrors()) {
            $markup  = sprintf($this->_errorFormat, $inputGroupAddon, $content);
        } else {
            $markup  = sprintf($this->_format, $inputGroupAddon, $content);
        }
        return $markup;
    }
}
