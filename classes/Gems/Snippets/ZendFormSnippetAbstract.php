<?php

namespace Gems\Snippets;

abstract class ZendFormSnippetAbstract extends \Zalt\Snippets\Zend\ZendFormSnippetAbstract
{
    protected int $layoutAutoWidthFactor = 0;

    protected function createForm(array $options = [])
    {
        $this->_form = new \Gems\Form($options);

        return $this->_form;
    }
}
