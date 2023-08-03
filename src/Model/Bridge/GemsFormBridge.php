<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Bridge;

use Gems\Form\Element\OnOffEdit;
use MUtil\Form\Element\ToggleCheckboxes;
use Zalt\Ra\Ra;

/**
 * @package    Gems
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
class GemsFormBridge extends \Zalt\Snippets\ModelBridge\ZendFormBridge
{
    public function addOnOffEdit($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        $options = $this->_mergeOptions(
            $name, $options, ['onOffEditFor', 'onOffEditValue'],
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        if (! isset($options['class'])) {
            $options['class'] = 'on-off-edit';
        } else {
            $options['class'] .= ' on-off-edit';
        }

        return $this->_addToForm($name, 'Radio', $options);
    }

    /**
     * @param string $name
     * @param mixed $arrayOrKey1 Ra::pairs() name => value array
     * @return \MUtil\Form\Element\ToggleCheckboxes
     * @throws \Zend_Form_Exception
     */
    public function addToggleCheckboxes($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = $this->_mergeOptions(
            $name,
            Ra::pairs(func_get_args(), 1),
            self::DISPLAY_OPTIONS,
            ['selectorName']
        );

        if (! isset($options['label'])) {
            if (isset($options['selectorName']) && $this->metaModel->has($options['selectorName'], 'label')) {
                $options['label'] = sprintf('Toggle %s', $this->metaModel->get($options['selectorName'], 'label'));
            } else {
                $options['label'] = 'Toggle';
            }
        }
        $element = new ToggleCheckboxes($name, $options);

        $this->form->addElement($element);

        return $element;
    }
}