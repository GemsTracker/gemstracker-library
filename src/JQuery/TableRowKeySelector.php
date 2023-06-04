<?php

/**
 * @package    Gems
 * @subpackage JQuery
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\JQuery;

/**
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class TableRowKeySelector extends \Gems\JQuery\JQueryExtenderAbstract
{
    protected $id;
    protected $jqueryParams = array('currentClass' => 'currentRow');
    protected $localScriptFiles = '/gems/js/tableRowKeySelector.js';
    protected $name = 'tableRowKeySelector';

    public function __construct($elementOrId = null, $args = null)
    {
        $args = \MUtil\Ra::args(func_get_args(),
            array(
                'element' => '\\MUtil\\Html\\ElementInterface',
                'attrib'  => '\\MUtil\\Html\\AttributeInterface',
                'id'      => 'is_string'
                ), null, false);

        parent::__construct($args);
    }

    public function getSelector()
    {
        return '#' . $this->id;
    }

    public function setAttrib(\MUtil\Html\AttributeInterface $attribute)
    {
        $this->setId($attribute->get());
        return $this;
    }

    public function setElement(\MUtil\Html\ElementInterface $element)
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

