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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Short description for class
 *
 * Long description for class (if any)...
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Snippets_EditRoundSnippetAbstract extends Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     * Required
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var int Gems round id
     */
    protected $roundId;

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     *
     * @var int $userId The current user
     */
    protected $userId = 0;

    /**
     * @var Gems_Util
     */
    protected $util;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->loader && parent::checkRegistryRequestsAnswers();
    }

    protected function beforeSave()
    {
        if (isset($this->formData['org_specific_round']) && $this->formData['org_specific_round'] == 1) {
            $this->formData['gro_organizations'] = '|' . implode('|', $this->formData['organizations']) . '|';
        } else {
            $this->formData['gro_organizations'] = null;
        }
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        return $this->trackEngine->getRoundModel(true, $this->createData ? 'create' : 'edit');
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('round', 'rounds', $count);
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        if ($this->createData) {
            return $this->_('Add new round');
        } else {
            return parent::getTitle();
        }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->trackEngine && (! $this->trackId)) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        if ($this->trackId) {
            // Try to get $this->trackEngine filled
            if (! $this->trackEngine) {
                // Set the engine used
                $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->trackId);
            }

        } else {
            return false;
        }

        if (! $this->roundId) {
            $this->roundId = $this->request->getParam(Gems_Model::ROUND_ID);
        }

        $this->createData = (! $this->roundId);

        return parent::hasHtmlOutput();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        if ($this->createData && !$this->request->isPost()) {
            $this->formData = $this->trackEngine->getRoundDefaults() + $this->formData;
        }

        // Check the survey name
        $surveys = $this->util->getTrackData()->getAllSurveys();
        if (isset($surveys[$this->formData['gro_id_survey']])) {
            $this->formData['gro_survey_name'] = $surveys[$this->formData['gro_id_survey']];
        } else {
            // Currently required
            $this->formData['gro_survey_name'] = '';
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        parent::saveData();

        if ($this->createData && (! $this->roundId)) {
            $this->roundId = $this->formData['gro_id_round'];
        }


        if ($this->formData['gro_valid_for_source'] == 'tok'
         && $this->formData['gro_valid_for_field']  == 'gto_valid_from'
         && empty($this->formData['gro_valid_for_id'])) {
            // Special case we should insert the current roundID here
            $this->formData['gro_valid_for_id'] = $this->roundId;

            // Now save, don't call saveData again to keep changed message as is
            $model          = $this->getModel();
            $this->formData = $model->save($this->formData);
        }

        $this->trackEngine->updateRoundCount($this->userId);
    }
}
