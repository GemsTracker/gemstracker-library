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
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Tracker;

/**
 * Describes the use of a track in a text paragraph.
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TrackUsageTextDetailsSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * When true the name is show as a header
     * @var boolean
     */
    protected $showHeader = false;

    /**
     * Optional: the display data of the track shown
     *
     * @var array
     */
    protected $trackData;

    /**
     * Optional, can be source of the $trackId
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * REQUIRED: the id of the track shown
     *
     * Or must be extracted from $trackData or $trackEngine
     *
     * @var int
     */
    protected $trackId;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();

        if (! $this->trackData) {
            $html->h2($this->_('Unknown track'));
            $this->addMessage(sprintf($this->_('Unknown track id %s'), $this->trackId));
            return $html;
        }

        if ($this->showHeader) {
            $html->h2(sprintf($this->_('%s track'), $this->trackData['gtr_track_name']));
        }

        if (isset($this->trackData['gtr_date_until']) && $this->trackData['gtr_date_until']) {
            $html->pInfo(
                sprintf(
                    $this->_('This track can be assigned from %s until %s.'),
                    \MUtil_Date::format($this->trackData['gtr_date_start'], \Zend_Date::DATE_LONG),
                    \MUtil_Date::format($this->trackData['gtr_date_until'], \Zend_Date::DATE_LONG))
                );

        } else {
            $html->pInfo(
                sprintf(
                    $this->_('This track can be assigned since %s.'),
                    \MUtil_Date::format($this->trackData['gtr_date_start'], \Zend_Date::DATE_LONG))
                );
        }

        return $html;
    }

    /**
     * Used by ProjectTracksAction_>showAction
     *
     * @deprecated
     * @return array
     */
    public function getTrackData()
    {
        return $this->trackData;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! $this->trackData) {
            if (! $this->trackId) {
                if ($this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
                    $this->trackId = $this->trackEngine->getTrackId();
                } else {
                    return false;
                }
            }

            $this->trackData = $this->db->fetchRow('SELECT * FROM gems__tracks WHERE gtr_id_track = ?', $this->trackId);
        } elseif (! $this->trackId) {
            $this->trackId = $this->trackData['gtr_id_track'];
        }

        return parent::hasHtmlOutput();
    }
}
