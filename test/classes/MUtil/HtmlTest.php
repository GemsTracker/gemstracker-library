<?php

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Unit test for class MUtil_Html
 *
 * @author     Michiel Rook <info@touchdownconsulting.nl>
 * @package    MUtil
 * @subpackage Html
 */
class MUtil_HtmlTest extends PHPUnit_Framework_TestCase
{
    public function testValidCreator()
    {
        $creator = MUtil_Html::getCreator();
        
        $this->assertInstanceOf('MUtil_Html_Creator', $creator);
    }
    
    public function testValidRenderer()
    {
        $renderer = MUtil_Html::getRenderer();
        
        $this->assertInstanceOf('MUtil_Html_Renderer', $renderer);
    }
    
    public function testDiv()
    {
        $div = MUtil_Html::div('bar', array('id' => 'foo'));
        
        $this->assertInstanceOf('MUtil_Html_HtmlElement', $div);
        $this->assertEquals('div', $div->getTagName());
        $this->assertEquals('foo', $div->getAttrib('id'));
    }
}
