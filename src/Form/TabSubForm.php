<?php

/**
 * A special displaygroup, to be displayed in a jQuery tab. Main difference is in the decorators.
 *
 * @author 175780
 * @filesource
 * @package Gems
 * @subpackage Form
 */
namespace Gems\Form;

class TabSubForm extends \Gems\Form\TableForm
{
    /**
     * For backward compatibility, allow \MUtil\Html calls to set or append to the title
     *
     * @param string $method
     * @param array $args
     * @return self|string
     */
    public function __call($method, $args)
    {
        if ('render' == substr($method, 0, 6)) {
            return parent::__call($method, $args);
        }

        $elem = \MUtil\Html::createArray($method, $args);

        $value = $this->getAttrib('title');

        $value .= $elem->render($this->getView());

        $this->setAttrib('title', $value);

        //As the decorator might have been added already, update it also
        $decorator = $this->getDecorator('TabPane');
        $options   = $decorator->getOption('jQueryParams');

        $options['title'] = strip_tags($value);

        $decorator->setOption('jQueryParams', $options);

        return $this;
    }
    
    /**
     * Load default decorators
     *
     * @return self
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements');
            $this->addDecorator(array('tab' => 'HtmlTag'), array('tag' => 'div', 'class' => 'displayGroup'))
                 ->addDecorator('TabPane', array('jQueryParams' => array('containerId' => 'mainForm',
                                                                         'title' => $this->getAttrib('title')),
                                                 'class' => $this->getAttrib('class')));
        }
        return $this;
    }
}
