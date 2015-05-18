<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class MUtil_Bootstrap_Form_Element_Multiselect extends MUtil_Bootstrap_Form_Element_Select
{
    /**
     * 'multiple' attribute
     * @var string
     */
    public $multiple = 'multiple';

    /**
     * Multiselect is an array of values by default
     * @var bool
     */
    protected $_isArray = true;
}

