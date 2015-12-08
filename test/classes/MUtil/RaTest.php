<?php

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Unit test for class MUtil_Ra
 *
 * @author     Michiel Rook <info@touchdownconsulting.nl>
 * @package    MUtil
 * @subpackage Ra
 */
class MUtil_RaTest extends PHPUnit_Framework_TestCase
{
    protected $_columnTest = array(
        'r1' => array('c1' => 1, 'c2' => 'c1'),
        'r2' => array('c1' => 2, 'c2' => 'c2'),
        'r3' => array('c1' => 3),
    );

    public function testArgsDouble()
    {
        $args = MUtil_Ra::args(array(array('a' => 'b'), array('a' => 'c')));
        $this->assertEquals($args, array('a' => 'c'));
    }

    public function testArgsSkipOrName()
    {
        $args = MUtil_Ra::args(array(0 => array(0 => 'f', 1 => array('o' => '0', 0 => 'b')), 1 => array('a' => array('r' => 'r'))), 1);
        $this->assertEquals($args, array('a' => array('r' => 'r')));
    }

    public function testArgsDefaults()
    {
        $args = MUtil_Ra::args(array('r1'), array('class1', 'class2'), array('class1' => 'odd', 'class2' => 'even'));
        $this->assertEquals($args, array('class1' => 'r1', 'class2' => 'even'));
    }

    public function testBraceKeys()
    {
        $args = MUtil_Ra::braceKeys(array(0 => 'a', 'b' => 'c'), '{', '}');
        $this->assertEquals($args, array('{0}' => 'a', '{b}' => 'c'));
    }

    public function testBraceKeysLeftOnly()
    {
        $args = MUtil_Ra::braceKeys(array(0 => 'a', 'b' => 'c'), '"');
        $this->assertEquals($args, array('"0"' => 'a', '"b"' => 'c'));
    }

    public function testColumnRelaxed()
    {
        $args = MUtil_Ra::column('c2', $this->_columnTest, MUtil_Ra::RELAXED);
        $this->assertEquals($args, array('r1' => 'c1', 'r2' => 'c2'));
    }

    public function testColumnRelaxedEmpty()
    {
        $args = MUtil_Ra::column('c3', $this->_columnTest, MUtil_Ra::RELAXED);
        $this->assertEmpty($args);
    }

    public function testColumnRelaxedSkips()
    {
        $args = MUtil_Ra::column('c2', $this->_columnTest, MUtil_Ra::RELAXED);
        $this->assertNotContains('r3', array_keys($args));
    }

    public function testColumnStrict()
    {
        $args = MUtil_Ra::column('c2', $this->_columnTest, MUtil_Ra::STRICT);
        $this->assertEquals($args, array('r1' => 'c1', 'r2' => 'c2', 'r3' => null));
    }

    public function testFlatten()
    {
        $args = MUtil_Ra::args(array(0 => array(0 => 'f', 1 => array('o' => '0', 0 => 'b')), 1 => array('a' => array('r' => 'r'))));
        $this->assertEquals($args, array(0 => 'f', 'o' => '0', 1 => 'b', 'a' => array('r' => 'r')));
    }

    public function testFindKeysExists()
    {
        $data = array(
            'row1' => array('c1' => 'a', 'c2' => 'd', 'c3' => 'g', 'c4' => 'j'),
            'row2' => array('c1' => 'b', 'c2' => 'e', 'c3' => 'h', 'c4' => 'k'),
            'row3' => array('c1' => 'c', 'c2' => 'f', 'c3' => 'i', 'c4' => 'l'),
        );
        $keys = array(
            'c1' => 'b',
            'c3' => 'h',
        );
        $this->assertEquals(MUtil_Ra::findKeys($data, $keys), 'row2');
    }

    public function testFindKeysExistsNot()
    {
        $data = array(
            'row1' => array('c1' => 'a', 'c2' => 'd', 'c3' => 'g', 'c4' => 'j'),
            'row2' => array('c1' => 'b', 'c2' => 'e', 'c3' => 'h', 'c4' => 'k'),
            'row3' => array('c1' => 'c', 'c2' => 'f', 'c3' => 'i', 'c4' => 'l'),
        );
        $keys = array(
            'c1' => 'm',
            'c3' => 'o',
        );
        $this->assertNull(MUtil_Ra::findKeys($data, $keys));
    }

    public function testFindKeysExistsWrong()
    {
        $data = array(
            'row1' => array('c1' => 'a', 'c2' => 'd', 'c3' => 'g', 'c4' => 'j'),
            'row2' => array('c1' => 'b', 'c2' => 'e', 'c3' => 'h', 'c4' => 'k'),
            'row3' => array('c1' => 'c', 'c2' => 'f', 'c3' => 'i', 'c4' => 'l'),
        );
        $keys = array(
            'c1' => 'b',
            'c3' => 'h',
        );
        $this->assertNotEquals(MUtil_Ra::findKeys($data, $keys), 'row3');
    }

    public function testKeySplit()
    {
        $args = array(0 => '0', 'a' => 'a', 1 => '1', 'b' => 'b', '2' => '2');
        list($nums, $strings) = MUtil_Ra::keySplit($args);
        $this->assertEquals($nums, array(0 => '0', 1 => '1', '2' => '2'));
        $this->assertEquals($strings, array('a' => 'a', 'b' => 'b'));
    }

    public function testKeySplitNumOnly()
    {
        $args = array(0 => '0', 1 => '1', '2' => '2');
        list($nums, $strings) = MUtil_Ra::keySplit($args);
        $this->assertEquals($nums, array(0 => '0', 1 => '1', '2' => '2'));
        $this->assertEquals($strings, array());
    }

    public function testKeySplitStringOnly()
    {
        $args = array('a' => 'a', 'b' => 'b');
        list($nums, $strings) = MUtil_Ra::keySplit($args);
        $this->assertEquals($nums, array());
        $this->assertEquals($strings, array('a' => 'a', 'b' => 'b'));
    }

    public function testKeySplitEmpty()
    {
        $args = array();
        list($nums, $strings) = MUtil_Ra::keySplit($args);
        $this->assertEquals($nums, array());
        $this->assertEquals($strings, array());
    }

    public function testNonScalars1()
    {
        $a = new stdClass();
        $a->b = 'c';
        $args = array('a', 'b', $a, 1);
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array($a));
    }

    public function testNonScalars2()
    {
        $a = new ArrayObject(array('a', 'b', 1));
        $args = array('a', 'b', $a, 1);
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array($a));
    }

    public function testNonScalars3()
    {
        $a = new ArrayObject(array('a', 'b', 1));
        $args = array('a', 'b', array($a), 1);
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array($a));
    }

    public function testNonScalarsEmpty()
    {
        $args = array();
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array());
    }

    public function testNonScalarsNested()
    {
        $args = array('a', 'b', array(0, 1));
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array());
    }

    public function testNonScalarsNone()
    {
        $args = array('a', 'b', 1);
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array());
    }

    public function testNonScalarsNull()
    {
        $args = null;
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array());
    }

    public function testNonScalarsString()
    {
        $args = '';
        $result = MUtil_Ra::nonScalars($args);
        $this->assertEquals($result, array());
    }
}
