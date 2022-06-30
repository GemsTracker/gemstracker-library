<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test\Condition;

/**
 * Description of InvalidCondition
 *
 * @author Dutchguys
 */
class TestCondition extends \Gems\Condition\RoundConditionAbstract {
    public function getHelp() {
        return 'help';
    }

    public function getModelFields($context, $new) {
        return array();
    }

    public function getName() {
        return 'name';
    }

    public function getNotValidReason($value, $context) {
        return 'invalid';
    }

    public function getRoundDisplay($trackId, $roundId) {
        return 'display';
    }

    public function isRoundValid(\Gems_Tracker_Token $token) {
        return true;
    }

    public function isValid($value, $context) {
        return true;
    }

}
