<?php

/**
 * Copyright (c) 2012, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
class Gems_Util_TokenData extends MUtil_Translate_TranslateableAbstract
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
     * @return Zend_Db_Expr
     */
    public function getStatusExpression()
    {
        return new Zend_Db_Expr("
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
}
