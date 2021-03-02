<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Util;

use Gems\Util\SiteConsole;
use PHPUnit_Extensions_Database_DataSet_ArrayDataSet;

/**
 *
 * @package    Gems
 * @subpackage Util
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteUtilNoDbTest extends \Gems_Test_DbTestAbstract
{
    /**
     * @var SiteUtil
     */
    protected $siteUtil;

    /**
     * @inheritDoc
     */
    protected function getDataSet()
    {
        return new PHPUnit_Extensions_Database_DataSet_ArrayDataSet([]);
    }

    /**
     * @return string
     */
    protected function getInitSql()
    {
        // The database is EMPTY!
        return '';
    }
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $settings         = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $settings         = $settings->toArray();
        $settings['salt'] = 'vadf2646fakjndkjn24656452vqk';
        $project          = new \Gems_Project_ProjectSettings($settings);
        $this->project    = $project;

        $this->util = $this->loader->getUtil();
        $cache      = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        $roles      = new \Gems_Roles($cache);
        $acl        = $roles->getAcl();

        $this->siteUtil = $this->util->getSites();
    }

    /**
     * No url for console
     */
    public function testGetConsoleOrgs()
    {
        $site = $this->siteUtil->getSiteForCurrentUrl();

        $this->assertEquals('https://test.example.site', $site->getUrl());
        $this->assertEquals('Gems\\Util\\SiteUrl', get_class($site));
        $this->assertEquals(1, count($site->getUrlOrganizations()));
        $this->assertEquals(\Gems_User_UserLoader::getNotOrganizationArray(), $site->getUrlOrganizations());
    }

    public function testCurrentUrl()
    {
        $site = $this->siteUtil->getSiteForCurrentUrl();

        $this->assertEquals('https://test.example.site', $site->getUrl());
        $this->assertEquals('Gems\\Util\\SiteUrl', get_class($site));
    }
    
    /**
     * No url for console
     */
    public function testIsConsole()
    {
        // Unit test enabled set \MUtil_Console::isConsole() to false
        \Zend_Session::$_unitTestEnabled = false;
        $site = $this->siteUtil->getSiteForCurrentUrl();
        \Zend_Session::$_unitTestEnabled = true;

        $this->assertEquals(SiteConsole::CONSOLE_URL, $site->getUrl());
        $this->assertEquals('Gems\\Util\\SiteConsole', get_class($site));
    }
    
    public function testNewUrl()
    {
        $site = $this->siteUtil->getSiteForUrl('https://localhost/newExample');

        $this->assertEquals('https://localhost/newExample', $site->getUrl());
        $this->assertEquals('Gems\\Util\\SiteUrl', get_class($site));
    }
}