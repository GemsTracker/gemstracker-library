<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Utility functions for token string functions
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Tracker_Token_TokenLibrary extends Gems_Registry_TargetAbstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    protected $tokenCaseSensitive;
    protected $tokenDisplayFormat;
    protected $tokenFormat;
    protected $tokenFrom;
    protected $tokenReuse;
    protected $tokenTo;


    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if (isset($this->project->tokens['chars'])) {
            $this->tokenChars = $this->project->tokens['chars'];
        } else {
            throw new Gems_Exception_Coding('Required project.ini setting "tokens.chars" is missing.');
        }

        if (isset($this->project->tokens['format'])) {
            $this->tokenFormat = $this->project->tokens['format'];
            $this->tokenDisplayFormat = str_replace("\t", '\\', str_replace(array('\\\\', '\\'), array("\t", ''), $this->tokenFormat));
        } else {
            throw new Gems_Exception_Coding('Required project.ini setting "tokens.format" is missing.');
        }

        if (isset($this->project->tokens['from'])) {
            $this->tokenFrom = $this->project->tokens['from'];

            if (isset($this->project->tokens['to'])) {
                $this->tokenTo = $this->project->tokens['to'];
            } else {
                $this->tokenTo = null;
            }
            if (strlen($this->tokenFrom) != strlen($this->tokenTo)) {
                throw new Gems_Exception_Coding('Project.ini setting "token.from" does not have the same length as argument "token.to".');
            }

        } else {
            $this->tokenFrom = null;
            $this->tokenTo = null;
        }

        if (isset($this->project->tokens['case'])) {
            $this->tokenCaseSensitive = $this->project->tokens['case'];
        } else {
            $this->tokenCaseSensitive = ! ($this->tokenChars === strtolower($this->tokenChars));
        }

        if (isset($this->project->tokens['reuse'])) {
            $this->tokenReuse = intval($this->project->tokens['reuse']);
        } else {
            $this->tokenReuse = 0;
        }

        return true;
    }

    /**
     * Creates a new token with a new random token Id
     *
     * @param array $tokenData
     * @param int $userId Id of the user who takes the action (for logging)
     * @return string The new token Id
     */
    public function createToken(array $tokenData, $userId)
    {
        $current = new MUtil_Db_Expr_CurrentTimestamp();

        $tokenData['gto_changed']    = $current;
        $tokenData['gto_changed_by'] = $userId;
        $tokenData['gto_created']    = $current;
        $tokenData['gto_created_by'] = $userId;

        // Wait till the last nanosecond with creating the token id
        $tokenData['gto_id_token']   = $this->createTokenId();

        $this->db->insert('gems__tokens', $tokenData);

        if (Gems_Tracker::$verbose) {
            MUtil_Echo::r($tokenData, 'Created token: ' . $tokenData['gto_id_token']);
        }

        return $tokenData['gto_id_token'];
    }

    /**
     * Generates a random token and checks for uniqueness
     *
     * @return string A non-existing token
     */
    protected function createTokenId()
    {
        $max = strlen($this->tokenChars) - 1;
        $len = strlen($this->tokenFormat);

        do {
            $out = '';
            for ($i = 0; $i < $len; $i++) {
                if ('\\' == $this->tokenFormat[$i]) {
                    $i++;
                    $out .= $this->tokenFormat[$i];
                } else {
                    $out .= $this->tokenChars[mt_rand(0, $max)];
                }
            }

        } while ($this->db->fetchOne('SELECT gto_id_token FROM gems__tokens WHERE gto_id_token = ?', $out));

        return $out;
    }

    /**
     * Removes all unacceptable characters from the input token and inserts any fixed characters left out
     *
     * @param string $token
     * @return string Reformatted token
     */
    public function filter($token)
    {
        if ($this->tokenFrom) {
            $token = strtr($token, $this->tokenFrom, $this->tokenTo);
        }
        if (! $this->tokenCaseSensitive) {
            $token = strtolower($token);
        }

        $formatLength = strlen($this->tokenFormat);
        $tokenLength  = strlen($token);
        $tokenPos     = 0;
        $resultToken  = '';

        for ($formatPos = 0; ($formatPos < $formatLength) && ($tokenPos < $tokenLength); $formatPos++) {
            if ('\\' == $this->tokenFormat[$formatPos]) {
                $formatPos++;
                $resultToken .= $this->tokenFormat[$formatPos];

            } else {
                while (false === strpos($this->tokenChars, $token[$tokenPos])) {
                    $tokenPos++;
                    if ($tokenPos >= $tokenLength) {
                        return $resultToken;
                    }
                }
                $resultToken .= $token[$tokenPos];
                $tokenPos++;
            }
        }
        if ($tokenPos < $tokenLength) {
            $resultToken .= ' ?' . substr($token, $tokenPos);
        }

        return $resultToken;
    }

    /**
     *
     * @return boolean True if case sensitive
     */
    public function getCaseSensitive()
    {
        return $this->tokenCaseSensitive;
    }

    /**
     * Display format describing token format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->tokenDisplayFormat;
    }

    /**
     * The characters that should not occur in a token
     *
     * @see getFrom
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->tokenFrom;
    }

    /**
     *
     * @return int The length a token is allowed to have.
     */
    public function getLength()
    {
        return strlen($this->tokenDisplayFormat);
    }

    /**
     * The number of days after completion a token can be used to look up other not completed tokens
     *
     * @return int
     */
    public function getReuse()
    {
        return $this->tokenReuse;
    }

    /**
     * The characters that replace characters that should not occur in a token
     *
     * @see getFrom()
     *
     * @return string
     */
    public function getTo()
    {
        return $this->tokenTo;
    }

    /**
     * True if after completion a token can be used to look up other not completed tokens
     *
     * @return boolean
     */
    public function hasReuse()
    {
        return $this->tokenReuse >= 0;
    }
}
