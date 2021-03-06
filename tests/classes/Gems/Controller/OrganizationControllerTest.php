<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Controller;

use ControllerTestAbstract;

/**
 * Description of RespondentControllerTest
 *
 * @author mdekk
 */
class OrganizationControllerTest extends ControllerTestAbstract
{

    public $tempDir;

    public function setUp()
    {
        $this->setPath(GEMS_TEST_DIR . '/data/controller');
        parent::setUp();

        $this->tempDir = GEMS_ROOT_DIR . '/var/tmp';

        $this->_fixSetup();
        $this->_fixUser();
    }

    public static function tearDownAfterClass()
    {
        // Cleanup created files
        $iterator = new \GlobIterator(GEMS_ROOT_DIR . '/var/tmp/export-*', \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO);
        foreach ($iterator as $fileinfo) {
            unlink($fileinfo->getPathname());
        }
    }

    /**
     * Test export
     *
     * @dataProvider ExportProvider
     */
    public function testExport($expectedFile, $type, $options)
    {
        \Zend_Session::$_unitTestEnabled = false; // Run cli @see \MUtil_Console
        $cache                           = \GemsEscort::getInstance()->cache;
        /* @var $cache \Zend_Cache_Core */
        $cache->clean();
        \MUtil_Batch_BatchAbstract::unload('export_data');  // Make sure there are no leftovers

        $params = [
            'step'     => 'batch',
            'type'     => $type,
            $type      => $options,
            'progress' => 'run'
        ];

        $req = $this->getRequest();
        $req->setPost($params);
        $this->dispatch('/organization/export');

        $iterator = new \GlobIterator($this->tempDir . '/export-*', \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO);
        $expected = file_get_contents($this->getPath() . '/' . $expectedFile);
        $datasetName = $this->getDataSetAsString(false);

        // Make sure there is exactly one exported file
        $this->assertEquals(1, $iterator->count(), sprintf('Number of files does not match expected.', $iterator->count()));

        foreach ($iterator as $fileName => $fileInfo) {
            if ($type == "StreamingExcelExport") {
                // We extract the sheet1 and compare that to the saved (expected) sheet1
                $actual = file_get_contents('zip://'. $fileInfo->getPathname() . '#xl/worksheets/sheet1.xml');
            } else {
                $actual = file_get_contents($fileInfo->getPathname());
            }
            unlink($fileInfo->getPathname());
            $this->assertEquals($expected, $actual);
        }

        $this->reset();
        \Zend_Session::destroy();
        \Zend_Registry::getInstance()->_unsetInstance();
    }

    public function exportProvider()
    {
        return [
            [
                'OrganizationController-Export-expected#0.csv',
                'CsvExport',
                [
                    'format'    => [
                        'addHeader',
                        'formatVariable',
                        'formatAnswer'
                    ],
                    'delimiter' => ','
                ]
            ],
            [
                'OrganizationController-Export-expected#1.csv',
                'CsvExport',
                [
                    'format'    => [
                        'formatAnswer'
                    ],
                    'delimiter' => ';'
                ]
            ],
            [
                'OrganizationController-Export-expected#2.csv',
                'CsvExport',
                [
                    'format'    => [
                        'addHeader'
                    ],
                    'delimiter' => ';'
                ]
            ],
            [
                'OrganizationController-Export-expected#3.xml',
                'StreamingExcelExport',
                [
                    'format'    => [
                        'formatVariable',
                        'formatAnswer',
                        'formatDate'
                    ],
                ]
            ],
        ];
    }
}
