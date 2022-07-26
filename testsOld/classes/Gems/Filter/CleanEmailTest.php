<?php

namespace Gems\Validator;

class CleanEmailFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Zend_Filter_Interface
     */
    protected $filter;
    
    public function setUp()
    {
        parent::setUp();
        $this->filter = new \Gems\Filter\CleanEmail();
    }

    /**
     *
     * @dataProvider inputProvider
     */
    public function testFilter($input, $expected)
    {
        $this->assertEquals($expected, $this->filter->filter($input));
    }

    public function inputProvider()
    {
        return [
            'mailto' => ['mailto:some@email.com', 'some@email.com'],
            'whitespace' => [' some@email.com ', 'some@email.com'],
            'brackets' => ['<some@email.com> Someone with Email', 'some@email.com'],
            'brackets2' => ['Someone with Email <some@email.com>', 'some@email.com']
        ];
    }

}
