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

use Gems\Audit\AuditLog;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;
use Gems\Snippets\Token\DeleteTrackTokenSnippet;
use Gems\Tracker\Respondent;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
    protected array $editItems = ['gr2o_comments'];

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected array $exhibitItems = ['gr2o_patient_nr', 'gr2o_id_organization'];

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected array $hiddenItems = ['grs_id_user'];

    /**
     * @var RespondentModel
     */
    protected $model;

    /**
     * The item containing the reception code field
     *
     * @var string
     */
    protected string $receptionCodeItem = 'gr2o_reception_code';

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
    protected ?string $unDeleteRight = 'pr.respondent.delete';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        CurrentUserRepository $currentUserRepository,
        protected Model $modelLoader,
        protected ReceptionCodeRepository $receptionCodeRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper, $currentUserRepository);

        $this->requestUndelete = $this->unDelete = $this->isUndeleting();
    }

    /**
     * Creates the model
     *
     * @return FullDataInterface
     */
    protected function createModel(): FullDataInterface
    {
        return $this->model;
    }

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array code name => description
     */
    public function getReceptionCodes()
    {
        if ($this->unDelete) {
            $output = $this->receptionCodeRepository->getRespondentRestoreCodes();
            if (array_key_exists(ReceptionCodeRepository::RECEPTION_OK, $output) && (! $output[ReceptionCodeRepository::RECEPTION_OK])) {
                $output[ReceptionCodeRepository::RECEPTION_OK] = $this->_('OK');
            }
            return $output;
        }
        return $this->receptionCodeRepository->getRespondentDeletionCodes();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        if (! $this->requestInfo->isPost()) {
            if ($this->respondent instanceof Respondent) {
                $this->formData = $this->respondent->getArrayCopy();
            }
        }

        if (! $this->formData) {
            parent::loadFormData();
        }

        $metaModel = $this->getModel()->getMetaModel();

        $metaModel->set('restore_tracks', [
            'label' => $this->_('Restore tracks'),
            'description' => $this->_('Restores tracks with the same code as the respondent.'),
            'elementClass' => 'Checkbox',
        ]);

        if (! array_key_exists('restore_tracks', $this->formData)) {
            $this->formData['restore_tracks'] = 1;
        }
        return $this->formData;
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
     * Set what to do when the form is 'finished'.
     */
    protected function setAfterSaveRoute(): void
    {
        // Default is just go to the index
        if (! $this->afterSaveRouteUrl) {
            $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl('respondent.show', $this->requestInfo->getRequestMatchedParams());
        }

        parent::setAfterSaveRoute();
    }

    /**
     * Hook performing actual save
     *
     * @param string $newCode
     * @param int $userId
     * @return int
     */
    public function setReceptionCode($newCode, $userId): int
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
                $this->routeAction        = 'index';
            } else {
                // Just a stop code
                $count = 0;
                $this->addMessage(sprintf($this->plural('Stopped %d track.', 'Stopped %d tracks.', $count), $count));
            }
        }

        return 1;
    }
}
