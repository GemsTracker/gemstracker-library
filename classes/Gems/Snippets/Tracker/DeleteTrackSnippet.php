<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class DeleteTrackSnippet extends ChangeReceptionCodeSnippetAbstract
{
    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $editItems = array('gr2t_comment');

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $exhibitItems = array(
        'gr2o_patient_nr', 'respondent_name', 'gtr_track_name', 'gr2t_track_info', 'gr2t_start_date',
        );

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected $hiddenItems = array('gr2t_id_respondent_track', 'gr2t_id_user', 'gr2t_id_organization');

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
    protected $receptionCodeItem = 'gr2t_reception_code';

    /**
     * Required
     *
     * @var \Gems\Tracker\RespondentTrack
     */
    protected $respondentTrack;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show-track';

    /**
     * Optional
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

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
        if (! $this->model instanceof \Gems\Tracker\Model\TrackModel) {
            $tracker     = $this->loader->getTracker();
            $this->model = $tracker->getRespondentTrackModel();

            if (! $this->trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
                $this->trackEngine = $this->respondentTrack->getTrackEngine();
            }
            $this->model->applyEditSettings($this->trackEngine);
        }

        $this->model->set('restore_tokens', 'label', $this->_('Restore tokens'),
                'description', $this->_('Restores tokens with the same code as the track.'),
                'elementClass', 'Checkbox'
                );

        return $this->model;
    }

    /**
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('respondent', 'show', $this->_('Show respondent'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'edit-track', $this->_('Edit track'));

        return $links;
    }

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array
     */
    public function getReceptionCodes()
    {
        $rcLib = $this->util->getReceptionCodeLibrary();

        if ($this->unDelete) {
            return $rcLib->getTrackRestoreCodes();
        }

        return $rcLib->getTrackDeletionCodes();
    }

    /**
     * Called after loadFormData() in loadForm() before the form is created
     *
     * @return boolean Are we undeleting or deleting?
     */
    public function isUndeleting()
    {
        if ($this->respondentTrack->hasSuccesCode()) {
            return false;
        }

        $this->editItems[] = 'restore_tokens';
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
        $oldCode = $this->respondentTrack->getReceptionCode();
        
        if (! $newCode instanceof \Gems\Util\ReceptionCode) {
            $newCode = $this->util->getReceptionCode($newCode);
        }

        // Use the repesondent track function as that cascades the consent code
        $changed = $this->respondentTrack->setReceptionCode($newCode, $this->formData['gr2t_comment'], $userId);

        if ($this->unDelete) {
            $this->addMessage($this->_('Track restored.'));

            if (isset($this->formData['restore_tokens']) && $this->formData['restore_tokens']) {                
                $count = $this->respondentTrack->restoreTokens($oldCode, $newCode);
                
                $this->addMessage(sprintf($this->plural(
                        '%d token reception codes restored.',
                        '%d tokens reception codes restored.',
                        $count
                        ), $count));
            }
        } else {
            if ($newCode->isStopCode()) {
                $this->addMessage($this->_('Track stopped.'));
            } else {
                $this->addMessage($this->_('Track deleted.'));
            }
        }

        return $changed;
    }
}
