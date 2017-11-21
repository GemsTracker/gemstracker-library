<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class that bundles information on tokens
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Util_TokenData extends \MUtil_Translate_TranslateableAbstract
{
    /**
     * Returns a status code => decription array
     *
     * @static $status array
     * @return array
     */
    public function getEveryStatus()
    {
        static $status;

        if ($status) {
            return $status;
        }

        $status = array(
            'U' => $this->_('Valid from date unknown'),
            'W' => $this->_('Valid from date in the future'),
            'O' => $this->_('Open - can be answered now'),
            'A' => $this->_('Answered'),
            'M' => $this->_('Missed deadline'),
            'D' => $this->_('Token does not exist'),
            );

        return $status;
    }

    /**
     * Returns the class to display the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusClass($value)
    {
        switch ($value) {
            case 'A':
                return 'answered';
            case 'M':
                return 'missed';
            case 'O':
                return 'open';
            case 'U':
                return 'unknown';
            case 'W':
                return 'waiting';
            default:
                return 'empty';
        }
    }

    /**
     * Returns the decription to add to the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusDescription($value)
    {
        $status = $this->getEveryStatus();

        if (isset($status[$value])) {
            return $status[$value];
        }

        return $status['D'];
    }

    /**
     * An expression for calculating the token status
     * 
     * @return \Zend_Db_Expr
     */
    public function getStatusExpression()
    {
        return new \Zend_Db_Expr("
            CASE
            WHEN gto_id_token IS NULL OR grc_success = 0 THEN 'D'
            WHEN gto_completion_time IS NOT NULL         THEN 'A'
            WHEN gto_valid_from IS NULL                  THEN 'U'
            WHEN gto_valid_from > CURRENT_TIMESTAMP      THEN 'W'
            WHEN gto_valid_until < CURRENT_TIMESTAMP     THEN 'M'
            ELSE 'O'
            END
            ");
    }

    /**
     * Returns the decription to add to the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusIcon($value)
    {
        static $status;
        if (is_null($status)) {           
            $spanU = \MUtil_Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanU->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanU->i(array('class' => 'fa fa-question fa-stack-1x fa-inverse', 'renderClosingTag' => true));
            
            $spanW = \MUtil_Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanW->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            
            $spanO = \MUtil_Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanO->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanO->i(array('class' => 'fa fa-arrow-up fa-stack-1x fa-inverse', 'renderClosingTag' => true));
            
            $spanA = \MUtil_Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanA->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            $spanA->i(array('class' => 'fa fa-check fa-stack-1x fa-inverse', 'renderClosingTag' => true));
            
            $spanM = \MUtil_Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanM->i(array('class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true));
            
            $spanD = \MUtil_Html::create('span', array('class' => 'fa-stack', 'renderClosingTag' => true));
            $spanD->i(array('class' => 'fa fa-times fa-stack-2x', 'renderClosingTag' => true));
            
            $status = array(                
            'U' => $spanU,
            'W' => $spanW,
            'O' => $spanO,
            'A' => $spanA,
            'M' => $spanM,
            'D' => $spanD,
            );
        }

        if (isset($status[$value])) {
            return $status[$value];
        }

        return $status['D'];
    }
}
