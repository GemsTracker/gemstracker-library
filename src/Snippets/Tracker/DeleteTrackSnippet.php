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

use Gems\Audit\AuditLog;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\ReceptionCode;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
    protected array $editItems = ['gr2t_comment'];

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected array $exhibitItems = [
        'gr2o_patient_nr', 'respondent_name', 'gtr_track_name', 'gr2t_track_info', 'gr2t_start_date',
        ];

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected array $hiddenItems = ['gr2t_id_respondent_track', 'gr2t_id_user', 'gr2t_id_organization'];

    /**
     * @var Tracker\Model\TrackModel|Tracker\Model\RespondentTrackModel
     */
    protected $model;

    /**
     * The item containing the reception code field
     *
     * @var string
     */
    protected string $receptionCodeItem = 'gr2t_reception_code';

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
    protected $routeAction = 'show';

    /**
     * Optional
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        CurrentUserRepository $currentUserRepository,
        protected ReceptionCodeRepository $receptionCodeRepository,
        protected Tracker $tracker,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper, $currentUserRepository);
    }


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
    protected function createModel(): FullDataInterface
    {
        if (! $this->model instanceof \Gems\Tracker\Model\TrackModel) {
            $this->model = $this->tracker->getRespondentTrackModel();

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
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array
     */
    public function getReceptionCodes()
    {
        if ($this->unDelete) {
            return $this->receptionCodeRepository->getTrackRestoreCodes();
        }

        return $this->receptionCodeRepository->getTrackDeletionCodes();
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
     * Set what to do when the form is 'finished'.
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->respondentTrack && ! $this->afterSaveRouteUrl) {
            $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl('respondent.tracks.show', $this->requestInfo->getRequestMatchedParams());
        }

        parent::setAfterSaveRoute();
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

        if (! $newCode instanceof ReceptionCode) {
            $newCode = $this->receptionCodeRepository->getReceptionCode($newCode);
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
