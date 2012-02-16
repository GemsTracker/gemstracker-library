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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
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
 * Refresh the attributes of all tokens
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Batch_RefreshTokenAttributesBatch extends MUtil_Batch_BatchAbstract
{
    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * Add a token check step to the batch
     *
     * @param string $tokenId A token id
     * @return Gems_Tracker_Batch_UpdateAttributesBatch (Continuation pattern)
     */
    public function addToken($tokenId)
    {
        $this->addStep('updateAttributes', $tokenId);

        return $this;
    }

    /**
     * Add token check steps to the batch
     *
     * @param array $tokenIds An array of token ids
     * @return Gems_Tracker_Batch_UpdateAttributesBatch (Continuation pattern)
     */
    public function addTokens(array $tokenIds)
    {
        foreach ($tokenIds as $tokenId) {
            $this->addStep('updateAttributes', $tokenId);
        }

        return $this;
    }

    /**
     * String of messages from the batch
     *
     * Do not forget to reset() the batch if you're done with it after
     * displaying the report.
     *
     * @param boolean $reset When true the batch is reset afterwards
     * @return array
     */
    public function getMessages($reset = false)
    {

        $cAll    = $this->count();
        $cTokens = $this->getCounter('changedTokens');

        $messages = parent::getMessages($reset);

        array_unshift($messages, sprintf($this->translate->_('Checked %d token.'), $cAll));
        if ($cTokens == 0) {
            $messages[] = $this->translate->_('No attributes were updated.');
        } else {
            $messages[] = sprintf($this->translate->plural('%d token changed.', '%d tokens changed.', $cTokens), $cTokens);
        }

        return $messages;
    }

    /**
     * Update the attributes of a token, if the token is
     * already in the source.
     *
     * @param string $tokenId A token id
     */
    protected function updateAttributes($tokenId)
    {
        $token = $this->tracker->getToken($tokenId);

        if ($token->inSource()) {
            $survey = $token->getSurvey();
            if ($survey->copyTokenToSource($token, '')) {
                $this->addToCounter('changedTokens');
            }
        }
    }
}
