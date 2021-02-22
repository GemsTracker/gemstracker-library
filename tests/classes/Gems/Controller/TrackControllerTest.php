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
class TrackControllerTest extends \ControllerTestAbstract
{
    public $tempDir;

    /**
     *
     * @var int
     */
    public $organizationIdNr = 1;

    public function setUp()
    {
        $this->setPath(GEMS_TEST_DIR . '/data/controller');
        parent::setUp();

        $this->_fixSetup();
        $this->_fixUser();
    }

    /**
     * Test create respondent track
     *
     */
    public function testCreateTrack()
    {
        $params = [
            'gr2t_id_track'        => 1,
            'save_button'          => 'Add track',
            'gr2t_id_user'         => 1234,
            'gr2t_id_organization' => 1
        ];

        // $this->setup();

        $req = $this->getRequest();
        $req->setPost($params);
        $req->setParam('tr', 1);
        $req->setParam('id1', 'abc');
        $req->setParam('id2', 1);

        // First get the csrf token
        $this->dispatch('/track/create');
        $body = $this->getResponse()->getBody();
        // error_log($body);
        if (preg_match('/name="no_csrf" value="(.*?)" id="no_csrf"/', $body, $matches)) {
            $csrf = $matches[1];
        } else {
            $this->fail('Unable to obtain csrf token');
        }

        $this->resetResponse();

        print_r(\GemsEscort::getInstance()->db->fetchAll("SELECT * FROM gems__organizations"));
        // Now submit the form
        $req->setPost('no_csrf', $csrf);
        $req->setMethod('post');
        $this->dispatch('/track/create');
        // echo $this->getResponse()->getBody();
        $loader   = \GemsEscort::getInstance()->getLoader();

        // print_r(\GemsEscort::getInstance()->db->fetchAll("SELECT * FROM gems__respondent2track"));
        $actual   = $loader->getTracker()->getRespondentTrack(2)->getFieldData();
        $expected = [
            'f__1'     => 'default',
            'f__2'     => null,
            'code'     => 'default',
            'datecode' => null,
        ];
        $this->assertEquals($expected, $actual);
        \MUtil_Batch_BatchAbstract::unload('tmp-track-2');  // Make sure there are no leftovers
    }

    /*public function testCorrect() {
        $req = $this->getRequest();
        $req->setParam('id', 'abcd-efgh');

        // First get the csrf token
        $this->dispatch('/track/correct');
        $body = $this->getResponse()->getBody();
        if (preg_match('/name="no_csrf" value="(.*?)" id="no_csrf"/', $body, $matches)) {
            $csrf = $matches[1];
        } else {
            $this->fail('Unable to obtain csrf token');
        }
        if (preg_match('/id="save_button" value="(.*?)"/', $body, $matches)) {
            $submit = $matches[1];
        } else {
            $this->fail('Unable to obtain submit button');
        }

        $this->resetResponse();

        // Now submit the form
        $req->setPost([
            'no_csrf' => $csrf,
            'save_button' => $submit
                ]);
        $req->setMethod('post');
        $this->dispatch('/track/correct');
        $body = $this->getResponse()->getBody();
        echo $body;
    }*/

}
