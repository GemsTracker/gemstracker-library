<?php

namespace Gems;

/**
 * Test linking to a github issues
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class ParsedownTest extends \PHPUnit_Framework_TestCase
{
    public function testOutput()
    {
        $parseDown = new Parsedown('GemsTracker/gemstracker-library');
        $result = $parseDown->parse("### test\r\n#1\r\nissue #360\r\n issue testorg/testrep#1");
        $expected = "<h3>test</h3>\n<h1>1</h1>\n<p>issue <a href=\"https://github.com/GemsTracker/gemstracker-library/issues/360\">#360</a>\nissue <a href=\"https://github.com/testorg/testrep/issues/1\">#1</a></p>";
        $this->assertEquals($expected, $result);
    }
}
