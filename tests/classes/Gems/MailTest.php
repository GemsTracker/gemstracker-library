<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of mailTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class Gem_MailTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * @var Gems_Mail
     */
    public $object;
    
    /**
     * @var Zend_Mail_Transport_File
     */
    public $transport;
    
    public function setUp() {
        parent::setUp();
        
        $this->object = new Gems_Mail();
        
        $options = array(
            'path' => realpath(dirname(__FILE__) . '/../../tmp'),
            'callback' => array($this, '_getFileName')
            );
        
        $this->transport = new Zend_Mail_Transport_File($options);
        $this->object->setDefaultTransport($this->transport);
        
    }
       
    /**
     * Return filename to use for this test
     */
    public function _getFileName() {
        return 'mailTest';
    }
    
    public function testNoFrom()
    {
        $this->setExpectedException('Gems_Exception', 'No sender email set!');
        $this->object->send();
    }
    
    public function testNoBody()
    {
        $this->setExpectedException('Zend_Mail_Transport_Exception', 'No body specified');
        $this->object->setFrom('test@gemstracker.org', 'test');
        $this->object->send($this->transport);
    }
    
    public function testNormal()
    {
        $this->object->setFrom('test@gemstracker.org', 'test');
        $this->object->setBodyText('test');
        $this->object->send($this->transport);
    }
}
