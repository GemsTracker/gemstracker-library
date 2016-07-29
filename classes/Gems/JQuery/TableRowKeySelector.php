<?php

/**
 * @package    Gems
 * @subpackage JQuery
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_JQuery_TableRowKeySelector extends \Gems_JQuery_JQueryExtenderAbstract
{
    protected $id;
    protected $jqueryParams = array('currentClass' => 'currentRow');
    protected $localScriptFiles = '/gems/js/tableRowKeySelector.js';
    protected $name = 'tableRowKeySelector';

    public function __construct($elementOrId = null, $args = null)
    {
        $args = \MUtil_Ra::args(func_get_args(),
            array(
                'element' => 'MUtil_Html_ElementInterface',
                'attrib'  => 'MUtil_Html_AttributeInterface',
                'id'      => 'is_string'
                ), null, false);

        parent::__construct($args);
    }

    public function getSelector()
    {
        return '#' . $this->id;
    }

    public function setAttrib(\MUtil_Html_AttributeInterface $attribute)
    {
        $this->setId($attribute->get());
        return $this;
    }

    public function setElement(\MUtil_Html_ElementInterface $element)
    {
        $this->setId($element->id);
        return $this;
    }

    public function setId($value)
    {
        $this->id = $value;
        return $this;
    }
}

