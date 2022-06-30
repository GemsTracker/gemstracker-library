<?php

namespace Test\Loader;

/**
 * Description of TargetLoaderTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl
 */
class TargetLoaderTest extends \PHPUnit_Framework_TestCase {
    
    /**
     *
     * @var \Gems_Loader_TargetLoaderAbstract
     */
    protected $loader;
    
    public function setUp() {
        parent::setUp();
        
        $container = null;
        $dirs = [];
        $this->loader = new \Gems_Loader_TargetLoaderAbstract($container, $dirs);
    }
    
    /**
     * There are two classes, one should not be returned since it is not of the right type
     */
    public function testListClasses()
    {
        $paths = [
            'Test_Condition_' => GEMS_TEST_DIR . '/data/Conditions'
        ];
        
        $classType = 'Gems\\Condition\\RoundConditionInterface';
        
        $result = $this->loader->listClasses($classType, $paths);
        
        $this->assertEquals(['\\Test\\Condition\\TestCondition' => 'name (\\Test\\Condition\\TestCondition)'], $result);
    }
    
    public function testListClassesIllegalDir()
    {
        $paths = [
            'Test_Condition_' => GEMS_TEST_DIR . '/data/Conditions',
            'Test_Illegal'    => GEMS_TEST_DIR . '/data/Conditions-notexisting',
        ];
        
        $classType = 'Gems\\Condition\\RoundConditionInterface';
        
        $result = $this->loader->listClasses($classType, $paths);
        
        $this->assertEquals(['\\Test\\Condition\\TestCondition' => 'name (\\Test\\Condition\\TestCondition)'], $result);
    }
    
    public function testListClassesNameMethod() {
        $paths = [
            'Test_Condition_' => GEMS_TEST_DIR . '/data/Conditions'
        ];
        
        $classType = 'Gems\\Condition\\RoundConditionInterface';
        
        $nameMethod = 'getHelp';
        
        $result = $this->loader->listClasses($classType, $paths, $nameMethod);
        
        $this->assertEquals(['\\Test\\Condition\\TestCondition' => 'help (\\Test\\Condition\\TestCondition)'], $result);
    }
}
