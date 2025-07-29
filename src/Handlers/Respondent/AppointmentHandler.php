<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Agenda\Agenda;
use Gems\Db\ResultFetcher;
use Gems\Exception;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\AppointmentModel;
use Gems\Model;
use Gems\Model\Dependency\ActivationDependency;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\Agenda\AllAppointmentsCheckSnippet;
use Gems\Snippets\Agenda\AppointmentFormSnippet;
use Gems\Snippets\Agenda\AppointmentShowSnippet;
use Gems\Snippets\Agenda\AppointmentTokensSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Track\TracksForAppointment;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use Gems\User\User;
use Gems\User\UserLoader;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @subpackage AppointmentHandler
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentHandler extends RespondentChildHandlerAbstract
{
    /**
     * Appointment ID of current request (if any)
     *
     * Set by loadParams()
     *
     * @var int
     */
    protected ?int $appointmentId = null;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterParameters = [
        'extraSort'   => ['gap_admission_time' => SORT_DESC],
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [
        'Agenda\\AppointmentsTableSnippet'
    ];

    /**
     * The parameters used for the check all action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $checkAllParameters = [
        'contentTitle' => 'getCheckAllTitle',
    ];

    /**
     * The snippets used for the check all action
     *
     * @var array Array of snippets name
     */
    protected array $checkAllSnippets = [
        'Generic\\ContentTitleSnippet',
        AllAppointmentsCheckSnippet::class,
        CurrentButtonRowSnippet::class,
        'Agenda\\ApplyFiltersInformation',
    ];


    /**
     * The parameters used for the check action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $checkParameters = [
        'csrfName'     => 'getCsrfTokenName',
        'csrfToken'    => 'getCsrfToken',
        'contentTitle' => 'getCheckTitle',
    ];

    /**
     * The snippets used for the check action
     *
     * @var mixed String or array of snippets name
     */
    protected array $checkSnippets = [
        'Generic\\ContentTitleSnippet',
        'Agenda\\AppointmentShortSnippet',
        'Agenda\\AppointmentCheckSnippet',
        CurrentButtonRowSnippet::class,
        'Agenda\\ApplyFiltersInformation',
    ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = [
        AppointmentFormSnippet::class,
    ];

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $indexParameters = [
        'contentTitle' => 'getContentTitle',
        ];

    /**
     * Organization ID of current request
     *
     * Set by loadParams()
     *
     * @var int
     */
    protected ?int $organizationId = null;

    /**
     * Respondent ID of current request
     *
     * Set by loadParams()
     *
     * @var string
     */
    protected ?int $respondentId = null;

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        ContentTitleSnippet::class,
        AppointmentShowSnippet::class,
        CurrentButtonRowSnippet::class,
        TracksForAppointment::class,
        AppointmentTokensSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        RespondentRepository $respondentRepository,
        CurrentUserRepository $currentUserRepository,
        protected Agenda $agenda,
        protected MaskRepository $maskRepository,
        protected Model $modelLoader,
        protected ResultFetcher $resultFetcher,
        protected UserLoader $userLoader,
        protected readonly AppointmentModel $appointmentModel,
    ) {
        parent::__construct($responder, $translate, $cache, $respondentRepository, $currentUserRepository);
    }

    /**
     * Perform checks on an appointment
     */
    public function checkAction(): array
    {
        if ($this->checkSnippets) {
            $params = $this->_processParameters($this->checkParameters);

            $this->addSnippets($this->checkSnippets, $params);
        }

        return [];
    }

    /**
     * Perform checks on all appointments
     */
    public function checkAllAction(): array
    {
        if ($this->checkSnippets) {
            $params = $this->_processParameters($this->checkAllParameters);

            $this->addSnippets($this->checkAllSnippets, $params);
        }

        return [];
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     */
    protected function createModel(bool $detailed, string $action): AppointmentModel
    {
        $this->loadParams();

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $this->appointmentModel->applyEditSettings($this->organizationId);

                if ($action === 'create') {
                    $locationId = $this->resultFetcher->fetchOne(
                        "SELECT gap_id_location
                                FROM gems__appointments
                                WHERE gap_id_user = ? AND gap_id_organization = ?
                                ORDER BY gap_admission_time DESC",
                        [$this->respondentId, $this->organizationId]
                    );

                    if ($locationId !== false) {
                        $this->appointmentModel->getMetaModel()->set('gap_id_location', ['default' => $locationId]);
                    }

                    $this->appointmentModel->getMetaModel()->set('gap_id_user', ['default' => $this->respondentId]);
                    $this->setRespondentIdInModel($this->appointmentModel->getMetaModel(), 'gap_id_user', 'gap_id_organization');
                    $this->appointmentModel->getMetaModel()->set('gap_manual_edit', ['default' => 1]);
                    $this->appointmentModel->getMetaModel()->set('gap_admission_time', ['default' => new \DateTimeImmutable('tomorrow')]);
                } else {
                    // When there is something saved, then set manual edit to 1
                    $this->appointmentModel->getMetaModel()->setSaveOnChange('gap_manual_edit');
                    $this->appointmentModel->getMetaModel()->setOnSave('gap_manual_edit', 1);
                }
            } else {
                $this->appointmentModel->applyDetailSettings();
            }
            if ($this->responder instanceof GemsSnippetResponder) {
                $menuHelper = $this->responder->getMenuSnippetHelper();
            } else {
                $menuHelper = null;
            }
            $metaModel = $this->appointmentModel->getMetaModel();
            $metaModel->addDependency(new ActivationDependency(
                $this->translate,
                $metaModel,
                $menuHelper,
            ));
        } else {
            $this->appointmentModel->applyBrowseSettings();
        }

        return $this->appointmentModel;
    }

    /**
     *
     * @return string
     */
    public function getCheckTitle(): string
    {
        return $this->_('Track field filter check for this appointment');
    }

    /**
     *
     * @return string
     */
    public function getCheckAllTitle(): string
    {
        $respondent = $this->getRespondent();
        return sprintf($this->_('Track field filter check for all appointments of %s'), $respondent->getPatientNumber());
    }

    /**
     * Helper function to get the informed title for the index action.
     *
     * @return string
     */
    public function getContentTitle(): string
    {
        $patientId = $this->request->getAttribute(Model::REQUEST_ID1);
        if ($patientId) {
            if ($this->maskRepository->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                return sprintf($this->_('Appointments for respondent number %s'), $patientId);
            }

            $respondent = $this->getRespondent();
            return sprintf($this->_('Appointments for respondent number %s: %s'), $patientId, $respondent->getName());
        }
        return $this->getIndexTitle();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Appointments');
    }

    /**
     * Get the respondent object
     *
     * @return Respondent
     */
    public function getRespondent(): Respondent
    {
        if (! $this->_respondent) {
            $id = $this->request->getAttribute(Model::APPOINTMENT_ID);
            $patientNr = $this->request->getAttribute(Model::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(Model::REQUEST_ID2);
            if ($id && ! ($patientNr || $organizationId)) {
                $appointment = $this->agenda->getAppointment($id);
                $this->_respondent = $appointment->getRespondent();

                if (! $this->_respondent->exists) {
                    throw new \Gems\Exception($this->_('Unknown respondent.'));
                }

                $this->currentUser->assertAccessToOrganizationId($organizationId, $appointment->getRespondentId());
                if ($this->responder instanceof GemsSnippetResponder) {
                    $this->_respondent->setMenu($this->responder->getMenuSnippetHelper(), $this->translate);
                }
            } else {
                $this->_respondent = parent::getRespondent();
            }
        }

        return $this->_respondent;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('appointment', 'appointments', $count);
    }

    /**
     * Loads and checks the request parameters
     *
     * @throws \Gems\Exception
     */
    protected function loadParams(): void
    {
        $patientNr           = $this->request->getAttribute(Model::REQUEST_ID1);
        $this->appointmentId = $this->request->getAttribute(Model::APPOINTMENT_ID);

        if ($this->appointmentId) {
            $select = $this->resultFetcher->getSelect('gems__appointments');
            $select->columns(['gap_id_user', 'gap_id_organization'])
                ->join('gems__respondent2org',
                    'gap_id_user = gr2o_id_user AND gap_id_organization = gr2o_id_organization',
                ['gr2o_patient_nr'])
                ->where(['gap_id_appointment' => $this->appointmentId]);
            $data = $this->resultFetcher->fetchRow($select);

            if ($data) {
                $this->organizationId = $data['gap_id_organization'];
                $this->respondentId   = $data['gap_id_user'];
            }
        } else {
            $this->organizationId = $this->request->getAttribute(Model::REQUEST_ID2);

            if ($patientNr && $this->organizationId) {
                $this->respondentId = $this->respondentRepository->getRespondentId($patientNr, $this->organizationId);
                $this->currentUser->assertAccessToOrganizationId($this->organizationId, $this->respondentId);
            } else {
                $this->currentUser->assertAccessToOrganizationId($this->organizationId, null);
            }
        }

        if (! $this->respondentId) {
            throw new Exception($this->_('Requested agenda data not available!'));
        } else {
            $orgs = $this->currentUser->getAllowedOrganizations();

            if (! isset($orgs[$this->organizationId])) {
                $org = $this->userLoader->getOrganization($this->organizationId);

                if ($org->exists()) {
                    throw new \Gems\Exception(
                            sprintf($this->_('You have no access to %s appointments!'), $org->getName())
                            );
                } else {
                    throw new Exception($this->_('Organization does not exist.'));
                }
            }
        }
    }
}
