<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

use Gems\Db\ResultFetcher;
use Gems\Exception\RespondentAlreadyExists;
use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Model\RespondentModel;
use Gems\Repository\OrganizationRepository;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\Tracker\Respondent;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Db\TableGateway\TableGateway;
use MUtil\Db\Expr\CurrentTimestamp;
use MUtil\Model;
use MUtil\StringUtil\StringUtil;
use MUtil\Validate\IsNot;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.1 Oct 24, 2016 6:35:29 PM
 */
class ChangeRespondentOrganization extends ModelFormSnippetAbstract
{
    /**
     *
     * @var int Number of records changed
     */
    protected int $_changed = 0;

    protected User $currentUser;

    /**
     * Only effective if copied
     *
     * @var boolean
     */
    protected bool $keepConsent = false;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected ResultFetcher $resultFetcher,
        protected OrganizationRepository $organizationRepository,
        protected UserLoader $userLoader,
        protected \Gems\Model $modelLoader,
        protected CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
        $this->currentUser = $this->currentUserRepository->getCurrentUser();
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param FormBridgeInterface $bridge
     * @param FullDataInterface $model
     */
    protected function addBridgeElements(FormBridgeInterface $bridge, FullDataInterface $model)
    {
        $this->saveLabel = $this->_('Change organization');

        $choices = [
            'share' => $this->_('Share between both organizations, keep tracks in old'),
            'copy' => $this->_('Move tracks but keep in both organization'),
            'move' => $this->_('Move tracks and remove from old organization'),
        ];

        $bridge->addRadio('change_method',
                'label', $this->_('Change method'),
                'multiOptions', $choices,
                'onchange', 'this.form.submit();',
                'required', true
                );

        $sql  = "SELECT gr2o_id_organization, gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_id_user = ?";

        $availableOrganizations = $this->organizationRepository->getOrganizationsWithRespondents();
        $shareOption            = $this->formData['change_method'] == 'share';
        $disabled               = [];
        $existingOrgs           = $this->resultFetcher->fetchPairs($sql, [$this->respondent->getId()]);
        foreach ($availableOrganizations as $orgId => &$orgName) {
            $orglabel = Html::create('spaced');
            $orglabel->strong($orgName);
            if ($orgId == $this->formData['orig_org_id']) {
                $disabled[] = $orgId;
                $orglabel->em($this->_('Is current organization.'));

            } elseif (isset($existingOrgs[$orgId])) {
                $orglabel->em(sprintf(
                        $this->_('Exists already with respondent nr %s.'),
                        $existingOrgs[$orgId]
                        ));

                if ($shareOption) {      
                    // Only disable when we just share the patient and he already exists
                    $disabled[] = $orgId;
                }
            }

            $orgName = $orglabel->render();
        }
        
        $bridge->addRadio('gr2o_id_organization',
                'label', $this->_('New organization'),
                'disable', $disabled,
                'escape', false,
                'multiOptions', $availableOrganizations,
                'onchange', 'this.form.submit();',
                'validator', new IsNot(
                        $disabled,
                        $this->_('You cannot change to this organization')
                        )
                );
        
        if (in_array($this->formData['gr2o_id_organization'], $disabled)) {
            // Selected organization is now unavailable, reset selection
            $this->formData['gr2o_id_organization'] = null;
        }
        
        // Only allow to set a patient number when not exists in selected destination organization
        $disablePatientNumber = array_key_exists($this->formData['gr2o_id_organization'], $existingOrgs);
        if ($disablePatientNumber) {
            $this->formData['gr2o_patient_nr'] = $existingOrgs[$this->formData['gr2o_id_organization']];
        }
        $bridge->addText('gr2o_patient_nr', 'label', $this->_('New respondent nr'),
                'disabled', $disablePatientNumber ? 'disabled' : null
                );
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
        // Communicate to user
        if ($changed >= 0) {
            switch ($this->formData['change_method']) {
                case 'share':
                    $message = $this->_('Shared %s with %s as %s');
                    break;

                case 'copy':
                    $message = $this->_('Moved %s with tracks to %s as %s');
                    break;
                case 'move':
                    $message = $this->_('Moved %s to %s as %s');
                    break;
                default:

            }
            $params = $this->requestInfo->getRequestMatchedParams();
            $this->addMessage(sprintf(
                    $message,
                    $params[Model::REQUEST_ID1],
                    $this->userLoader->getOrganization($this->formData['gr2o_id_organization'])->getName(),
                    $this->formData['gr2o_patient_nr']
                    ));

        } else {
            parent::afterSave($changed);
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        if ($this->model instanceof RespondentModel) {
            $model = $this->model;

        } else {
            if ($this->respondent instanceof Respondent) {
                $model = $this->respondent->getRespondentModel();

            } else {
                $model = $this->modelLoader->getRespondentModel(true);
            }
            $model->applyDetailSettings();
        }

        return $model;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->respondent instanceof Respondent) {
            if ($this->currentUser->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                return sprintf(
                        $this->_('Change organization of respondent nr %s'),
                        $this->respondent->getPatientNumber()
                        );
            }
            return sprintf(
                    $this->_('Change organization of respondent nr %s: %s'),
                    $this->respondent->getPatientNumber(),
                    $this->respondent->getName()
                    );
        }
        return $this->_('Change organization of respondent');
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        parent::loadFormData();

        if (! isset($this->formData['change_method'])) {
            $this->formData['change_method'] = null;
        }
        $params = $this->requestInfo->getRequestMatchedParams();
        if (isset($params[Model::REQUEST_ID2])) {
            $this->formData['orig_org_id'] = $params[Model::REQUEST_ID2];
        }
        
        return $this->formData;
    }

    /**
     * Hook containing the actual save code.
     *
     * Calls afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData(): int
    {
        $this->beforeSave();

        $params = $this->requestInfo->getRequestMatchedParams();

        $fromOrgId   = $params[Model::REQUEST_ID2];
        $fromPid     = $params[Model::REQUEST_ID1];
        $fromRespId  = $this->respondent->getId();
        $toOrgId     = $this->formData['gr2o_id_organization'];
        $toPatientId = $this->formData['gr2o_patient_nr'];

        switch ($this->formData['change_method']) {
            case 'share':
                $this->_changed = $this->saveShare($fromOrgId, $fromPid, $toOrgId, $toPatientId);
                break;

            case 'copy':
                $this->_changed = $this->saveShare($fromOrgId, $fromPid, $toOrgId, $toPatientId);
                if ($this->_changed >= 0) {
                    $this->_changed += $this->saveMoveTracks($fromOrgId, $fromRespId, $toOrgId, $toPatientId, false);
                } else {
                    $this->addMessage($this->_('ERROR: Tracks not moved!'));
                }
                break;

            case 'move':
                $this->_changed = $this->saveTo($fromOrgId, $fromPid, $toOrgId, $toPatientId);
                if ($this->_changed >= 0) {
                    $this->_changed += $this->saveMoveTracks($fromOrgId, $fromRespId, $toOrgId, $toPatientId, true);
                } else {
                    $this->addMessage($this->_('ERROR: Tracks not moved!'));
                }
                break;

            default:
                $this->_changed = 0;

        }

        // Message the save
        return $this->_changed;
    }

    /**
     *
     * @param int $fromOrgId
     * @param int $fromRespId
     * @param int $toOrgId
     * @param string $toPatientId
     * @param boolean $all When true log data and appointments are also moved
     * @return int 1 If saved
     */
    protected function saveMoveTracks($fromOrgId, $fromRespId, $toOrgId, $toPatientId, $all = false)
    {
        $tables = array(
            'gems__respondent2track'              => ['gr2t_id_user',      'gr2t_id_organization', true],
            'gems__tokens'                        => ['gto_id_respondent', 'gto_id_organization',  true],
            'gems__appointments'                  => ['gap_id_user',       'gap_id_organization',  $all],
            'gems__log_respondent_communications' => ['grco_id_to',        'grco_organization',  $all],
        );

        $changed   = 0;
        $currentTs = new CurrentTimestamp();
        $userId    = $this->currentUser->getUserId();

        foreach ($tables as $tableName => $settings) {
            list($respIdField, $orgIdField, $change) = $settings;

            if ($change) {
                $start = StringUtil::beforeChars($respIdField, '_');

                $values = [
                    $orgIdField            => $toOrgId,
                    $start . '_changed'    => $currentTs,
                    $start . '_changed_by' => $userId,
                    ];
                $where = [
                    $respIdField => $fromRespId,
                    $orgIdField => $fromOrgId,
                ];

                $table = new TableGateway($tableName, $this->resultFetcher->getAdapter());
                $changed += $table->update($values, $where);
            }
        }

        return $changed;
    }

    /**
     * Copy the respondent
     * 
     * @param int $fromOrgId
     * @param int $fromPatientId
     * @param int $toOrgId
     * @param string $toPatientId
     * @return int 1 If saved
     */
    protected function saveShare($fromOrgId, $fromPatientId, $toOrgId, $toPatientId)
    {
        /** @var RespondentModel $model */
        $model = $this->getModel();
        try {
            $result = $model->copyToOrg($fromOrgId, $fromPatientId, $toOrgId, $toPatientId, $this->keepConsent);
        } catch (RespondentAlreadyExists $exc) {
            $info = $exc->getInfo();
            switch ($info) {
                case RespondentAlreadyExists::OTHERPID:
                    $result = -1;
                    break;
                
                case RespondentAlreadyExists::OTHERUID:
                    $result = -2;
                    break;
                
                case RespondentAlreadyExists::SAME:
                default:
                    // Do nothing, already exists
                    $result = 0;
                    break;
            }
                
            $this->addMessage($exc->getMessage());
            return $result;
        }

        return $model->getChanged();
    }

    /**
     * Move the respondent
     * 
     * @param int $fromOrgId
     * @param int $fromPatientId
     * @param int $toOrgId
     * @param string $toPatientId
     * @return int 1 If saved
     */
    protected function saveTo($fromOrgId, $fromPatientId, $toOrgId, $toPatientId)
    {
        /** @var \Gems\Model\RespondentModel $model */
        $model = $this->getModel();
        try {
            $model->move($fromOrgId, $fromPatientId, $toOrgId, $toPatientId);    
        } catch (RespondentAlreadyExists $exc) {
            $info = $exc->getInfo();
            switch ($info) {
                case RespondentAlreadyExists::OTHERPID:
                    $result = -1;
                    break;
                
                case RespondentAlreadyExists::OTHERUID:
                    $result = -2;
                    break;
                
                case RespondentAlreadyExists::SAME:
                default:
                    // Do nothing, already exists
                    $result = 0;
                    break;
            }
                
            $this->addMessage($exc->getMessage());
            return $result;
        }        
        
        return $model->getChanged();
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return self
     */
    protected function setAfterSaveRoute(): self
    {
        $this->routeAction = 'show';

        if ($this->requestInfo->getCurrentAction() !== $this->routeAction) {
            $this->afterSaveRouteUrl = [
                'controller' => $this->requestInfo->getCurrentController(),
                'action' => $this->routeAction,
            ];

            $this->afterSaveRouteUrl[Model::REQUEST_ID1] = $this->formData['gr2o_patient_nr'];
            $this->afterSaveRouteUrl[Model::REQUEST_ID2] = $this->formData['gr2o_id_organization'];
        }

        return $this;
    }
}
