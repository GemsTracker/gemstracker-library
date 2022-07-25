<?php

namespace Gems\Form\Decorator;

class CountryInputGroupAddon extends \Gems\Form\Decorator\InputGroupAddon
{
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
        $element = $this->getElement();
        $value = $element->getValue();

        if (empty($content) && !empty($value)) {
            $content = $this->getFlagTag($value);
        }

        return parent::render($content);
    }


}
