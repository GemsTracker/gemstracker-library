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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Default controller for tracks containing a single round.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_TrackRoundAction extends Gems_Default_TrackRoundsAction
{
    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return Gems_Model_TrackModel
     */
    public function createModel($detailed, $action)
    {
        $model = parent::createModel($detailed, $action);

        if ($detailed) {
            switch ($action) {
                case 'edit':
                case 'show':
                    // Add missing information
                    if (! $this->_getParam(Gems_Model::ROUND_ID)) {
                        $roundId = $this->trackEngine->getFirstRoundId();

                        $this->_setParam(Gems_Model::ROUND_ID, $roundId);
                        $this->menu->getParameterSource()->setRoundId($roundId);
                    }
                    break;
            }
        }

        return $model;
    }

    /**
     * Edit a single round
     */
    public function editAction()
    {
        $trackId = $this->_getIdParam();

        if (! $trackId) {
            throw new Gems_Exception($this->_('Missing track identifier.'));
        }

        $menuSource = $this->menu->getParameterSource();
        $trackEngine = $this->loader->getTracker()->getTrackEngine($trackId);
        $trackEngine->applyToMenuSource($menuSource);
        $menuSource->setRequestId($trackId); // Tell the menu we're using track id as request id

        $this->addSnippets($trackEngine->getRoundEditSnippetNames(), 'roundId', $trackEngine->getFirstRoundId(), 'trackEngine', $trackEngine, 'trackId', $trackId);
    }
}
