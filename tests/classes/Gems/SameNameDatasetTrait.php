<?php

/**
 *
 * @package    Gems
 * @subpackage SameNameYamlDatasetTrait.php
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems;

use PHPUnit_Extensions_Database_DataSet_ArrayDataSet;

/**
 *
 * @package    Gems
 * @subpackage SameNameYamlDatasetTrait.php
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
trait SameNameDatasetTrait
{
    /**
     * Used to setup database for an individual testcase
     *
     * Will use <classname>_<testname>.yml|xml|csv or <classname>.yml|xml|csv
     *
     * @return \PHPUnit_Extensions_Database_DataSet_AbstractDataSet
     */
    protected function getDataSet()
    {
        //Dataset className.yml has the minimal data we need to perform our tests
        $reflector = new \ReflectionClass($this);
        $classFile = str_replace('.php', '', $reflector->getFileName());
        $testFile  = $classFile . '_' . $this->getName(false);
        $extensions = [ 
            'yml' => '\\PHPUnit_Extensions_Database_DataSet_YamlDataSet',
            'xml' => '\\PHPUnit_Extensions_Database_DataSet_YamlDataSet',
            'csv' => '\\PHPUnit_Extensions_Database_DataSet_CsvDataSet',
            ];
        
        foreach ($extensions as $extension => $loaderClass) {
            $name = $testFile . '.' . $extension;
            if (file_exists($name)) {
                return new $loaderClass($name);
            }
        }

        foreach ($extensions as $extension => $loaderClass) {
            $name = $classFile . '.' . $extension;
            if (file_exists($name)) {
                return new $loaderClass($name);
            }
        }

        return new PHPUnit_Extensions_Database_DataSet_ArrayDataSet([]);
    }
}