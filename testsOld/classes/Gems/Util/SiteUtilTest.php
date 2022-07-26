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

use Gems\SameNameDatasetTrait;
use Gems\Util\SiteConsole;
use Gems\Util\SiteUtil;
use PHPUnit_Extensions_Database_DataSet_IDataSet;

/**
 *
 * @package    Gems
 * @subpackage Util
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteUtilTest extends \Gems\Test\DbTestAbstract
{
    use SameNameDatasetTrait;

    /**
     * @var int Initial number of db orgs
     */
    protected $initOrgCount;

    /**
     * @var int Initial number of db sites
     */
    protected $initSiteCount;
    
    /**
     * @var SiteUtil
     */
    protected $siteUtil;

    /**
     * @return int Get the current number of sites
     */
    protected function getSiteCount()
    {
        return $this->db->fetchOne("SELECT COUNT(*) FROM gems__sites");        
    }

    public function providerTestBlocking()
    {
        return [
            ['https://localhost/orgThree', false, false],
            ['https://localhost/orgThree/blabla.php', false, false],
            ['https://localhost/orgWhatever', true, false],
            ['https://localhost/orgWhatever/gsjkhjk', true, false],
            ['https://localhost/somethingElse', false, true],
            ['https://localhost/somethingElse/blabla.php', false, 'https://localhost/somethingElse'],
            ['https://localhost/somethingElse/blabla.php', false, true],
        ];
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
        $project          = new \Gems\Project\ProjectSettings($settings);
        $this->project    = $project;

        $this->util = $this->loader->getUtil();
        $cache      = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        $roles      = new \Gems\Roles($cache);
        $acl        = $roles->getAcl();

        $this->siteUtil = $this->util->getSites();
        $this->siteUtil->getSiteLock()->unlock();
        
        $this->initOrgCount  = $this->db->fetchOne("SELECT COUNT(*) FROM gems__organizations WHERE gor_active = 1");
        $this->initSiteCount = $this->getSiteCount();
    }

    /**
     * No url for console
     * 
     * @dataProvider providerTestBlocking
     */
    public function testBlockedUrl($url, $shouldBeBlocked, $acceptLoginFirst)
    {
        if ($acceptLoginFirst) {
            // create first
            if (true === $acceptLoginFirst) {
                $this->siteUtil->getSiteForUrl($url, false);
            } else {
                $this->siteUtil->getSiteForUrl($acceptLoginFirst, false);
            }
        }
        
        $site = $this->siteUtil->getSiteByFullUrl($url, true);
        
        if ($shouldBeBlocked) {
            $this->assertTrue($site->isBlocked());
        } else {
            $this->assertFalse($site->isBlocked());
        }
    }
    
    /**
     * No url for console
     */
    public function testGetConsoleOrgs()
    {
        $site = $this->siteUtil->getSiteForCurrentUrl();

        $this->assertEquals(count($site->getUrlOrganizations()), $this->initOrgCount);
        $this->assertEquals('https://test.example.site', $site->getUrl());
        $this->assertEquals('Gems\\Util\\SiteUrl', get_class($site));
    }

    /**
     * No url for console
     */
    public function testIsConsole()
    {
        // Unit test enabled set \MUtil\Console::isConsole() to false
        \Zend_Session::$_unitTestEnabled = false;
        $site = $this->siteUtil->getSiteForCurrentUrl();
        \Zend_Session::$_unitTestEnabled = true;

        $this->assertEquals('Gems\\Util\\SiteConsole', get_class($site));
        $this->assertEquals(SiteConsole::CONSOLE_URL, $site->getUrl());
    }
    
    public function testNewUrl()
    {
        $url = 'https://localhost/newUrl';
        $site = $this->siteUtil->getSiteForUrl($url);

        $this->assertEquals('Gems\\Util\\SiteUrl', get_class($site));
        $this->assertEquals($url, $site->getUrl());
        $this->assertEquals($this->initOrgCount, count($site->getUrlOrganizations()));
        $this->assertEquals($this->initSiteCount + 1, $this->getSiteCount());
    }

    public function testGetNoneForAll()
    {
        $site = $this->siteUtil->getOneForAll();

        $this->assertNull($site);
    }

    public function testGetOneForAll()
    {
        $site = $this->siteUtil->getOneForAll();

        $this->assertEquals('https://localhost/orgAll', $site->getUrl());
        $this->assertEquals($this->initOrgCount, count($site->getUrlOrganizations()));
    }
    
    public function testUrlForOne()
    {
        $url  = 'https://localhost/orgOneTwo';
        $site = $this->siteUtil->getSiteForUrl($url);

        $this->assertEquals($url, $site->getUrl());
        $this->assertEquals(2, count($site->getUrlOrganizations()));
        $this->assertEquals($this->initSiteCount, $this->getSiteCount());
    }
}