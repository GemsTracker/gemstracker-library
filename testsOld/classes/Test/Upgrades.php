<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test;

/**
 * Description of Upgrades
 *
 * @author 175780
 */
class Upgrades extends \Gems\UpgradesAbstract
{
    public function __construct()
    {
        parent::__construct();
        
        $this->setContext('test');
        $this->register('True', null,  10);
        $this->register('False', null, 12);
        $this->register('Third', null, 8);
    }
    
    public function True()
    {
        return true;
    }
    
    public function False()
    {
        return false;
    }
    
    public function Third()
    {
        return true;
    }
    
}
