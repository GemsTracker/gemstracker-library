<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

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
class EditRoundSnippetAbstract extends \Gems\Snippets\ModelFormSnippetAbstract
{
    /**
     * Required
     *
     * @var \Gems\Loader
     */
    protected $loader;

    protected $onlyUsedElements = true;

    /**
     *
     * @var int \Gems round id
     */
    protected $roundId;

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * @var \Gems\Util
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
     * @return \MUtil\Model\ModelAbstract
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
     * {@see \MUtil\Registry\TargetInterface}.
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
            $this->roundId = $this->request->getParam(\Gems\Model::ROUND_ID);
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
        $surveys = $this->util->getTrackData()->getAllSurveys(false);
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
        // Check the survey name again, is sometimes removed
        $surveys = $this->util->getTrackData()->getAllSurveys(false);
        if (isset($surveys[$this->formData['gro_id_survey']])) {
            $this->formData['gro_survey_name'] = $surveys[$this->formData['gro_id_survey']];
        } else {
            // Currently required
            $this->formData['gro_survey_name'] = '';
        }

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

        $this->trackEngine->updateRoundCount($this->loader->getCurrentUser()->getUserId());
    }
}
