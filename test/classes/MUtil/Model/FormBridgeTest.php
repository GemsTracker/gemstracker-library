<?php
class MUtil_Model_FormBridgeTest extends PHPUnit_Framework_TestCase
{
    /**
     * The options array as set in the setUp()
     *
     * @var array
     */
    protected $options;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    public function setUp()
    {
        parent::setUp();

        $this->options = array(
            'date' => array(
                'dateFormat' => 'dd-MM-yyyy',
                'description' => 'dd-mm-yyyy',
                'size'        => 10,
                'jQueryParams' => array(
                    'buttonImage' => 'datepicker.png',
                    'changeMonth' => true,
                    'changeYear' => true,
                    'duration' => 'fast',
                    'showOn' => 'button'
                    )
                )
            );
        
        MUtil_Model_Bridge_FormBridge::setFixedOptions($this->options);
        
        parent::setUpBeforeClass();
    }

    public function tearDown()
    {

    }

    public function testOptions()
    {
        $model = $this->model;
        $form  = new Zend_Form();

        //Unchanged when not in the fixedOptions
        $options = array('description'=>'dd-mm-yyyy');
        $original = $options;
        MUtil_Model_Bridge_FormBridge::applyFixedOptions('input', $options);
        $this->assertEquals($original, $options);

        //Overruled and extended when in the fixedOptions
        $options = $original;
        MUtil_Model_Bridge_FormBridge::applyFixedOptions('date', $options);
        $expected = $this->options['date'];
        $this->assertEquals($expected, $options);
    }
}