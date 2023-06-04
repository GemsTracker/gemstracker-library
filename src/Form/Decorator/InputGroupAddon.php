<?php


namespace Gems\Form\Decorator;

class InputGroupAddon extends \Zend_Form_Decorator_ViewHelper
{
    public function render($content)
    {
        $content = parent::render($content);

        return sprintf('<div class="input-group-addon">%s</div>', $content);
    }
}
