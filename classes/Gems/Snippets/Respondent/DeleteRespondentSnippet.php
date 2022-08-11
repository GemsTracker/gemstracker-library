<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

use Gems\Model\RespondentModel;
use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;
use Gems\Tracker\Respondent;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 28-apr-2015 10:28:02
 */
class DeleteRespondentSnippet extends ChangeReceptionCodeSnippetAbstract
{
    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $editItems = [];

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $exhibitItems = ['gr2o_patient_nr', 'gr2o_id_organization'];

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected $hiddenItems = ['grs_id_user'];

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * The item containing the reception code field
     *
     * @var string
     */
    protected $receptionCodeItem = 'gr2o_reception_code';

    /**
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    /**
     * Optional right to check for undeleting
     *
     * @var string
     */
    protected $unDeleteRight = 'pr.respondent.undelete';

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // Do nothing, performed in setReceptionCode()
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if ($this->model instanceof RespondentModel) {
            $model = $this->model;

        } else {
            if ($this->respondent instanceof Respondent) {
                $model = $this->respondent->getRespondentModel();

            } else {
                $model = $this->loader->getModels()->getRespondentModel(true);;
            }
            $model->applyDetailSettings();
        }

        return $model;
    }

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array code name => description
     */
    public function getReceptionCodes()
    {
        $rcLib = $this->util->getReceptionCodeLibrary();

        if ($this->unDelete) {
            return $rcLib->getRespondentRestoreCodes();
        }
        return $rcLib->getRespondentDeletionCodes();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if (! $this->requestInfo->isPost()) {
            if ($this->respondent instanceof Respondent) {
                $this->formData = $this->respondent->getArrayCopy();
            }
        }

        if (! $this->formData) {
            parent::loadFormData();
        }

        $model = $this->getModel();

        $model->set('restore_tracks', 'label', $this->_('Restore tracks'),
            'description', $this->_('Restores tracks with the same code as the respondent.'),
            'elementClass', 'Checkbox'
        );

        if (! array_key_exists('restore_tracks', $this->formData)) {
            $this->formData['restore_tracks'] = 1;
        }
    }

    /**
     * Are we undeleting or deleting?
     *
     * @return boolean
     */
    public function isUndeleting()
    {
        if ($this->respondent->getReceptionCode()->isSuccess()) {
            return false;
        }

        $this->editItems[] = 'restore_tracks';
        return true;
    }

    /**
     * Hook performing actual save
     *
     * @param string $newCode
     * @param int $userId
     * @return $changed
     */
    public function setReceptionCode($newCode, $userId)
    {
        $oldCode = $this->respondent->getReceptionCode();
        $code    = $this->respondent->setReceptionCode($newCode);

        // Is the respondent really removed
        if ($code->isSuccess()) {
            $this->addMessage($this->_('Respondent restored.'));

            if ($this->formData['restore_tracks']) {
                $count = $this->respondent->restoreTracks($oldCode, $code);

                $this->addMessage(sprintf($this->plural('Restored %d track.', 'Restored %d tracks.', $count), $count));
            }

        } else {
            // Perform actual save, but not simple stop codes.
            if ($code->isForRespondents()) {
                $this->addMessage($this->_('Respondent deleted.'));
                $this->afterSaveRouteKeys = false;
                $this->resetRoute         = true;
                $this->routeAction        = 'index';
            } else {
                // Just a stop code
                $this->addMessage(sprintf($this->plural('Stopped %d track.', 'Stopped %d tracks.', $count), $count));
            }
        }

        return 1;
    }
}
