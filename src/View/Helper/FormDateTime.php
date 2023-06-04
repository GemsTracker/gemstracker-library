<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage View\Helper
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\View\Helper;

/**
 *
 * @package    Gems
 * @subpackage View\Helper
 * @since      Class available since version 1.9.2
 */
class FormDateTime extends \Zend_View_Helper_FormText
{
    protected function _getInfo($name, $value = null, $attribs = null, $options = null, $listsep = null)
    {
        $output = parent::_getInfo($name, $value, $attribs, $options, $listsep);
        
        unset($output['attribs']['dateFormat'], $output['attribs']['storageFormat']);
        
        return $output;
    }

    /**
     * Generates a 'text' element.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are used in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function formDateTime($name, $value = null, $attribs = null)
    {
        return $this->formText($name, $value, $attribs);
    }
}