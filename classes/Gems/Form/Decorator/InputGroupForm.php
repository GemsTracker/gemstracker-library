<?php


class Gems_Form_Decorator_InputGroupForm extends \Zend_Form_Decorator_ViewHelper
{
    /**
     * Change the current decorators
     *
     * @param \Zend_Form_Element $element
     * @param array $decorators
     */
    private function _applyDecorators(\Zend_Form_Element $element, array $decorators)
    {
        $element->clearDecorators();
        foreach ($decorators as $decorator) {
            call_user_func_array(array($element, 'addDecorator'), $decorator);
        }
    }

    /**
     * Render the element
     *
     * @param  string $content Content to decorate
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $elementId = $element->getId();

        if ($element instanceof \MUtil_Form_Element_Table) {
            $subforms = $element->getSubForms();
        } elseif ($element instanceof \Zend_Form)  {
            $cellDecorators = null;
            $subforms = [$element];
        }

        $elementOptions = [
            'class' => 'element-container',
        ];

        if ($elementId) {
            $elementOptions['id'] = $elementId;
        }

        $subformContainer = \MUtil_Html::create()->div($elementOptions);
        $hidden = [];

        if ($subforms) {
            foreach ($subforms as $subform) {
                $formContainer = $subformContainer->div(['class' => 'input-group', 'renderClosingTag' => true]);
                foreach ($subform->getElements() as $subelement) {
                    if ($subelement instanceof \Zend_Form_Element_Hidden) {
                        $this->_applyDecorators($subelement, [['ViewHelper']]);
                        $hidden[] = $subelement;
                    } else {
                        $formContainer->append($subelement);
                    }
                }
            }
            if ($hidden) {
                $formContainer->append($hidden);
            }
        }

        $view = $element->getView();

        return $subformContainer->render($view);

    }
}
