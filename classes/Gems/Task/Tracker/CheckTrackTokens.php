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
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CheckTrackTokens.php 502 2012-02-20 14:13:20Z mennodekker $
 */

/**
 * Checks a respondentTrack for changes, mostly started by Gems_Task_ProcessTokenCompletion
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Task_Tracker_CheckTrackTokens extends Gems_Task_TaskAbstract
{
    /**
     * @var Gems_Tracker
     */
    public $tracker;

    public function execute($respTrackData = null, $userId = null)
    {
        $this->tracker = $this->loader->getTracker();
        $respTrack = $this->tracker->getRespondentTrack($respTrackData);
        $this->_batch->addToCounter('checkedRespondentTracks');

        if ($result = $respTrack->checkTrackTokens($userId)) {
            $this->_batch->addToCounter('tokenDateCauses');
            $this->_batch->addToCounter('tokenDateChanges', $result);
        }

        if ($this->_batch->getCounter('tokenDateChanges')) {
                $this->_batch->setMessage('tokenDateChanges', sprintf($this->translate->_('%2$d token date changes in %1$d tracks.'), $this->_batch->getCounter('tokenDateCauses'), $this->_batch->getCounter('tokenDateChanges')));
        }

        $this->_batch->setMessage('checkedRespondentTracks', sprintf($this->translate->_('Checked %d tracks.'), $this->_batch->getCounter('checkedRespondentTracks')));
    }
}