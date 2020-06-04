<?php


class Gems_Form_Decorator_InputGroupAddon extends \Zend_Form_Decorator_ViewHelper
{
    public function render($content)
    {
        $content = parent::render($content);

        return sprintf('<div class="input-group-addon">%s</div>', $content);
    }
}
