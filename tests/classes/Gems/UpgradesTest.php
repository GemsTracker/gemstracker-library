<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems;

/**
 * Description of UpgradesTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class UpgradesTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @return \Test_Upgrades
     */
    protected function getExistingFile()
    {
        $object      = new \Test\Upgrades();
        $upgradeFile = GEMS_TEST_DIR . str_replace('/', DIRECTORY_SEPARATOR, '/data/upgrades/upgrades_existing.ini');
        $object->answerRegistryRequest('upgradeFile', $upgradeFile);
        $object->afterRegistry();

        return $object;
    }

    /**
     *
     * @return \Test_Upgrades
     */
    protected function getNotExistingFile()
    {
        $object      = new \Test\Upgrades();
        $upgradeFile = GEMS_TEST_DIR . str_replace('/', DIRECTORY_SEPARATOR, '/data/upgrades/upgrades_new.ini');
        if (file_exists($upgradeFile)) {
            unlink($upgradeFile);
        }
        $object->answerRegistryRequest('upgradeFile', $upgradeFile);
        $object->afterRegistry();

        return $object;
    }

    /**
     * If file does not exist, we assum the maxlevel is the current level
     */
    public function testNewFile()
    {
        $object = $this->getNotExistingFile();
        $this->assertEquals(12, $object->getLevel('test'));
    }

    public function testExistingFile()
    {
        $object = $this->getExistingFile();
        
        $expected = [
            'test' => [
                'maxLevel' => 12,
                'level'    => 10,
                'context'  => 'test'
            ]
        ];
        $this->assertEquals($expected, $object->getUpgradesInfo());
        $this->assertEquals($expected['test'], $object->getUpgradesInfo('test'));
        $this->assertEquals(null, $object->getUpgradesInfo('undefined'));
    }
    
    public function testNextLevel()
    {
        $object = $this->getExistingFile();
        $level = $object->getNextLevel('test');
        $this->assertEquals(12, $level);
    }

}
