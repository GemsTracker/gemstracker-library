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
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_ChangeTracker
{
    public $checkedTokens           = 0;
    public $createdTokens           = 0;
    public $checkedRespondentTracks = 0;
    public $deletedTokens           = 0;
    public $resultDataChanges       = 0;
    public $roundChangeUpdates      = 0;
    public $roundCompletionCauses   = 0;
    public $roundCompletionChanges  = 0;
    public $surveyCompletionChanges = 0;
    public $tokenDateCauses         = 0;
    public $tokenDateChanges        = 0;

    public function getMessages(\Zend_Translate $t)
    {
        if ($this->checkedRespondentTracks) {
            $messages[] = sprintf($t->_('Checked %d tracks.'), $this->checkedRespondentTracks);
        }
        if ($this->checkedTokens || (! $this->checkedRespondentTracks)) {
            $messages[] = sprintf($t->_('Checked %d tokens.'), $this->checkedTokens);
        }

        if ($this->hasChanged()) {
            if ($this->surveyCompletionChanges) {
                $messages[] = sprintf($t->_('Answers changed by survey completion event for %d tokens.'), $this->surveyCompletionChanges);
            }
            if ($this->resultDataChanges) {
                $messages[] = sprintf($t->_('Results and timing changed for %d tokens.'), $this->resultDataChanges);
            }
            if ($this->roundCompletionChanges) {
                $messages[] = sprintf($t->_('%d token round completion events caused changed to %d tokens.'), $this->roundCompletionCauses, $this->roundCompletionChanges);
            }
            if ($this->tokenDateChanges) {
                $messages[] = sprintf($t->_('%2$d token date changes in %1$d tracks.'), $this->tokenDateCauses, $this->tokenDateChanges);
            }
            if ($this->roundChangeUpdates) {
                $messages[] = sprintf($t->_('Round changes propagated to %d tokens.'), $this->roundChangeUpdates);
            }
            if ($this->deletedTokens) {
                $messages[] = sprintf($t->_('%d tokens deleted by round changes.'), $this->deletedTokens);
            }
            if ($this->createdTokens) {
                $messages[] = sprintf($t->_('%d tokens created to by round changes.'), $this->createdTokens);
            }
        } else {
            $messages[] = $t->_('No tokens were changed.');
        }

        return $messages;
    }

    public function hasChanged()
    {
        return $this->resultDataChanges || $this->surveyCompletionChanges || $this->roundCompletionChanges || $this->tokenDateCauses || $this->roundChangeUpdates || $this->createdTokens;
    }
}
