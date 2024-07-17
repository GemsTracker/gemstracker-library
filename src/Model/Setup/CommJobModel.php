<?php

namespace Gems\Model\Setup;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\Dependency\CommJob\SenderDependency;
use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Gems\Repository\CommJobRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\StaffRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Model\Dependency\SqlOptionsDependency;
use Zalt\Model\Dependency\ValueSwitchDependency;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\SqlOptionsType;
use Zalt\SnippetsActions\ApplyActionInterface;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\Model\ModelUniqueValidator;
use Zalt\Validator\SimpleEmail;

class CommJobModel extends GemsJoinModel implements ApplyActionInterface
{
    protected readonly int $currentUserId;

    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translator,
        CurrentUserRepository $currentUserRepository,
        protected readonly CommJobRepository $commJobRepository,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly StaffRepository $staffRepository,
        protected readonly TrackDataRepository $trackDataRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly Translated $translateUtil,
    )
    {
        parent::__construct('gems__comm_jobs', $metaModelLoader, $sqlRunner, $translator, 'commJobModel', true);

        $metaModelLoader->setChangeFields($this->metaModel, 'gcj');
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
        $this->applySettings();
    }

    public function applyAction(SnippetActionInterface $action): void
    {
        if ($action->isDetailed()) {
            $html = Html::create()->h4($this->_('Execution'));
            $this->metaModel->set('execution', [
                'default' => $html,
                'label' => ' ',
                'elementClass' => 'html',
                'value' => $html,
                'order' => 1100,
                SqlRunnerInterface::NO_SQL => true,
            ]);

            $this->metaModel->set('gcj_process_method', [
                'multiOptions' => $this->commJobRepository->getBulkProcessOptions(),
            ]);

            $this->metaModel->set('gcj_filter_days_between', [
                'label' => '',
                'elementClass' => 'Hidden',
                'required' => true,
                'validators[]' => 'Digits',
                'order' => 1500,
            ]);
            $this->metaModel->set('gcj_filter_max_reminders', [
                'label' => '',
                'elementClass' => 'Hidden',
                'description' => $this->_('1 means only one reminder will be send'),
                'required' => true,
                'validators[]' => 'Digits',
                'order' => 1600,
            ]);


            $html = Html::create()->h4($this->_('Sender'));
            $this->metaModel->set('send_from', [
                'default' => $html,
                'label' => ' ',
                'elementClass' => 'html',
                'value' => $html,
                'order' => 2100,
                SqlRunnerInterface::NO_SQL => true,
            ]);

            $this->metaModel->set('gcj_from_fixed', [
                'label' => '',
                'elementClass' => 'Hidden',
                'validators[mail]' => SimpleEmail::class,
                'order' => 2400,
            ]);

            $html = Html::create()->h4($this->_('Receiver'));
            $this->metaModel->set('send_to', [
                'default' => $html,
                'label' => ' ',
                'elementClass' => 'html',
                'value' => $html,
                'order' => 3100,
                SqlRunnerInterface::NO_SQL => true,
            ]);

            $html = Html::create()->h4($this->_('Survey selection'));
            $this->metaModel->set('selection', [
                'default' => $html,
                'label' => ' ',
                'elementClass' => 'html',
                'value' => $html,
                'order' => 4100,
                SqlRunnerInterface::NO_SQL => true,
            ]);

            $this->metaModel->set('gcj_id_organization', [
                'label' => $this->_('Organization'),
            ]);

            if ($action->isEditing()) {
                // Set the default round order
                $newOrder = $this->resultFetcher->fetchOne('SELECT MAX(gcj_id_order) FROM gems__comm_jobs');

                if ($newOrder) {
                    $this->metaModel->set('gcj_id_order', 'default', $newOrder + 10);
                }
            }
        }
    }

    public function applySettings()
    {
        $unselected = ['' => ''];
        $yesNo = $this->translateUtil->getYesNo();

        $this->metaModel->set('gcj_id_order', [
            'label' => $this->_('Execution order'),
            'description' => $this->_('Execution order of the communication jobs, lower numbers are executed first.'),
            'required' => true,
            'order' => 100,
            'validators[uniq]' => ModelUniqueValidator::class,
        ]);

        $this->metaModel->set('gcj_id_communication_messenger', [
            'label' => $this->_('Communication method'),
            'description' => $this->_('The communication method the message should be sent as. E.g. mail, sms'),
            'required' => true,
            'multiOptions' => $this->commJobRepository->getCommunicationMessengers(),
            'order' => 200,
        ]);

        $this->metaModel->set('gcj_id_message', [
            'label' => $this->_('Template'),
            'multiOptions' => $unselected + $this->commJobRepository->getCommTemplates('token'),
            'order' => 300,
        ]);


        $this->metaModel->set('gcj_active', [
            'label' => $this->_('Execution method'),
            'multiOptions' => $this->commJobRepository->getActiveOptions(),
            'required' => true,
            'description' => $this->_('Manual jobs run only manually, but not during automatic jobs. Disabled jobs not even then. '),
            'order' => 1200,
        ]);

        $this->metaModel->set('gcj_process_method', [
            'label' => $this->_('Processing Method'),
            'default' => 'O',
            'description' => $this->_('Only for advanced users'),
            'multiOptions' => $this->commJobRepository->getBulkProcessOptionsShort(),
            'order' => 1300,
        ]);

        $switches = [
            'R' => [
                'gcj_filter_days_between' => [
                    'elementClass' => 'Text',
                    'label' => $this->_('Days between reminders'),
                    'description' => $this->_('1 day means the reminder is send the next day')
                ],
                'gcj_filter_max_reminders' => [
                    'elementClass' => 'Text',
                    'label' => $this->_('Maximum reminders')
                ],
            ],
            'B' => [
                'gcj_filter_days_between' => [
                    'elementClass' => 'Text',
                    'label' => $this->_('Days before expiration'),
                    'description' =>''
                ],
            ],
            'E' => [
                'gcj_filter_days_between' => [
                    'elementClass' => 'Text',
                    'label' => $this->_('Days before expiration'),
                    'description' => ''
                ],
            ],
            'N' => [],
        ];
        $this->metaModel->addDependency([ValueSwitchDependency::class, $switches], 'gcj_filter_mode');
        $this->metaModel->set('gcj_filter_mode', [
            'label' => $this->_('Filter for'),
            'multiOptions' => $unselected + $this->commJobRepository->getBulkFilterOptions(),
            'order' => 1400,
        ]);

        $switches = [
            'F' => [
                'gcj_from_fixed' => [
                    'elementClass' => 'Text',
                    'label' => $this->_('From other')
                ]
            ],
            'O' => [],
            'S' => [],
            'U' => [],
        ];
        $this->metaModel->addDependency([ValueSwitchDependency::class, $switches], 'gcj_from_method');
        $this->metaModel->set('gcj_id_user_as', [
            'label' => $this->_('By staff member'),
            'multiOptions' => $unselected + $this->staffRepository->getActiveStaff(),
            'default' => $this->currentUserId,
            'description' => $this->_('Used for logging and possibly from address.'),
            'order' => 2200,
        ]);

        $fromMethods = $unselected + $this->commJobRepository->getBulkFromOptions();
        $this->metaModel->set('gcj_from_method', [
            'label' => $this->_('From address used'),
            'multiOptions' => $fromMethods,
            'order' => 2300,
        ]);


        $this->metaModel->set('gcj_target', [
            'label' => $this->_('Filler'),
            'default' => 0,
            'multiOptions' => $this->commJobRepository->getBulkTargetOptions(),
            'order' => 3200,
        ]);

        $this->metaModel->set('gcj_to_method', [
            'multiOptions' => $this->commJobRepository->getBulkToOptions(),
            'order' => 3250,
        ]);
        $fromMethods = $unselected + $this->commJobRepository->getBulkFromOptions();
        $this->metaModel->set('gcj_fallback_method', [
            'multiOptions' => $fromMethods,
            'order' => 3300,
        ]);
        $this->metaModel->set('gcj_fallback_fixed', [
            'validators[mail]' => SimpleEmail::class,
            'order' => 3400,
        ]);
        $this->metaModel->addDependency(SenderDependency::class);
        if ($this->metaModel->has('gcj_target_group')) {
            $anyGroup[''] = $this->_('(all groups)');
            $this->metaModel->set('gcj_target_group', [
                'label' => $this->_('Group'),
                'multiOptions' => $anyGroup + $this->commJobRepository->getAllGroups(),
                'order' => 3500,
            ]);
        }

        $anyTrack[''] = $this->_('(all tracks)');
        $this->metaModel->set('gcj_id_track', [
            'label' => $this->_('Track'),
            'multiOptions' => $anyTrack + $this->trackDataRepository->getAllTracks(),
            'order' => 4200,
        ]);

        $this->metaModel->set('gcj_round_description', [
            'label' => $this->_('Round'),
            'order' => 4300,
            SqlOptionsType::EMPTY_OPTION => $this->_('(all rounds)'),
            'type' => new SqlOptionsType(
                'gems__rounds',
                'gro_round_description',
                links: ['gcj_id_track' => 'gro_id_track'],
                fixedFilter: ['gro_round_description IS NOT NULL', 'gro_round_description != ""', 'gro_id_round != 0',],
            ),
        ]);

        $anySurvey[''] = $this->_('(all surveys)');
        $this->metaModel->set('gcj_id_survey', [
            'label' => $this->_('Survey'),
            'multiOptions' => $anySurvey + $this->trackDataRepository->getAllSurveys(false),
            'order' => 4400,
        ]);

        $organizations = $this->organizationRepository->getOrganizations();
        $anyOrganization[''] = $this->_('(all organizations)');
        $this->metaModel->set('gcj_id_organization', [
            'multiOptions' => $anyOrganization + $organizations,
            'order' => 4500,
        ]);

        if (count($organizations) > 1) {
            $this->metaModel->set('gcj_id_organization', [
                'label' => $this->_('Organization'),
            ]);
        }
    }
}