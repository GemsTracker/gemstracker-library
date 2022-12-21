<?php

namespace Gems\Model;

use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\CommJobRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\StaffRepository;
use Gems\Repository\TrackDataRepository;
use MUtil\Translate\Translator;
use Zalt\Html\Html;

class CommJobModel extends JoinModel
{
    protected int $currentUserId;

    public function __construct(
        protected CommJobRepository $commJobRepository,
        protected StaffRepository $staffRepository,
        protected TrackDataRepository $trackDataRepository,
        protected OrganizationRepository $organizationRepository,
        CurrentUserRepository $currentUserRepository,
        Translator $translator,
    )
    {
        parent::__construct('commJobs', 'gems__comm_jobs', 'gcj', true);
        $this->currentUserId = $currentUserRepository->getCurrentUser()->getUserId();
        $this->translate = $translator;
    }

    public function afterRegistry()
    {
        \Gems\Model::setChangeFieldsByPrefix($this, 'gcj');

        $unselected = ['' => ''];

        $this->set('gcj_id_order', [
            'label' => $this->_('Execution order'),
            'description' => $this->_('Execution order of the communication jobs, lower numbers are executed first.'),
            'required' => true,
            'order' => 100,
        ]);

        $this->set('gcj_id_communication_messenger', [
            'label' => $this->_('Communication method'),
            'description' => $this->_('The communication method the message should be sent as. E.g. mail, sms'),
            'required' => true,
            'multiOptions' => $this->commJobRepository->getCommunicationMessengers(),
            'order' => 200,
        ]);


        $this->set('gcj_id_message', [
            'label' => $this->_('Template'),
            'multiOptions' => $unselected + $this->commJobRepository->getCommTemplates('token'),
            'order' => 300,
        ]);

        $this->set('gcj_active', [
            'label' => $this->_('Execution method'),
            'multiOptions' => $this->commJobRepository->getActiveOptions(),
            'required' => true,
            'description' => $this->_('Manual jobs run only manually, but not during automatic jobs. Disabled jobs not even then. '),
            'order' => 1200,
        ]);

        $this->set('gcj_process_method', [
            'label' => $this->_('Processing Method'),
            'default' => 'O',
            'description' => $this->_('Only for advanced users'),
            'multiOptions' => $this->commJobRepository->getBulkProcessOptionsShort(),
            'order' => 1300,
        ]);
        $this->set('gcj_filter_mode', [
            'label' => $this->_('Filter for'),
            'multiOptions' => $unselected + $this->commJobRepository->getBulkFilterOptions(),
            'order' => 1400,
        ]);

        $this->set('gcj_id_user_as', [
            'label' => $this->_('By staff member'),
            'multiOptions' => $unselected + $this->staffRepository->getActiveStaff(),
            'default' => $this->currentUserId,
            'description' => $this->_('Used for logging and possibly from address.'),
            'order' => 2200,
        ]);

        $fromMethods = $unselected + $this->commJobRepository->getBulkFromOptions();
        $this->set('gcj_from_method', [
            'label' => $this->_('From address used'),
            'multiOptions' => $fromMethods,
            'order' => 2300,
        ]);

        $this->set('gcj_target', [
            'label' => $this->_('Filler'),
            'default' => 0,
            'multiOptions' => $this->commJobRepository->getBulkTargetOptions(),
            'order' => 3200,
        ]);

        if ($this->has('gcj_target_group')) {
            $anyGroup[''] = $this->_('(all groups)');
            $this->set('gcj_target_group', [
                'label' => $this->_('Group'),
                'multiOptions' => $anyGroup + $this->commJobRepository->getAllGroups(),
                'order' => 3500,
            ]);
        }

        $anyTrack[''] = $this->_('(all tracks)');
        $this->set('gcj_id_track', [
            'label' => $this->_('Track'),
            'multiOptions' => $anyTrack + $this->trackDataRepository->getAllTracks(),
            'order' => 4200,
        ]);

        $anyRound['']  = $this->_('(all rounds)');
        $defaultRounds = $anyRound + $this->trackDataRepository->getAllRoundDescriptions();
        $this->set('gcj_round_description', [
            'label' => $this->_('Round'),
            'multiOptions' => $defaultRounds,
            'variableSelect' => [
                'source' => 'gcj_id_track',
                'baseQuery' => $this->getRoundDescriptionQuery(),
                'ajax' => [
                    'controller' => 'comm-job',
                    'action' => 'roundselect'
                ],
                'firstValue' => $anyRound,
                'defaultValues' => $defaultRounds,
            ],
            'order' => 4300,
        ]);

        $anySurvey[''] = $this->_('(all surveys)');
        $this->set('gcj_id_survey', [
            'label' => $this->_('Survey'),
            'multiOptions' => $anySurvey + $this->trackDataRepository->getAllSurveys(true),
            'order' => 4400,
        ]);

        $organizations = $this->organizationRepository->getOrganizations();
        $anyOrganization[''] = $this->_('(all organizations)');
        $this->set('gcj_id_organization', [
            'multiOptions' => $anyOrganization + $organizations,
            'order' => 4500,
        ]);

        if (count($organizations) > 1) {
            $this->set('gcj_id_organization', [
                'label' => $this->_('Organization'),
            ]);
        }
    }

    public function applyDetailSettings()
    {
        $this->set('gcj_id_order', [
            'validator' => $this->createUniqueValidator('gcj_id_order'),
        ]);

        $html = Html::create()->h4($this->_('Execution'));
        $this->set('execution', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
            'order' => 1100,
        ]);

        $this->set('gcj_process_method', [
           'multiOptions' => $this->commJobRepository->getBulkProcessOptions(),
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
        $this->addDependency(['ValueSwitchDependency', $switches], 'gcj_filter_mode');

        $this->set('gcj_filter_days_between', [
            'label' => '',
            'elementClass' => 'Hidden',
            'required' => true,
            'validators[]' => 'Digits',
            'order' => 1500,
        ]);
        $this->set('gcj_filter_max_reminders', [
            'label' => '',
            'elementClass' => 'Hidden',
            'description' => $this->_('1 means only one reminder will be send'),
            'required' => true,
            'validators[]' => 'Digits',
            'order' => 1600,
        ]);

        $html = Html::create()->h4($this->_('Sender'));
        $this->set('send_from', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
            'order' => 2100,
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
        $this->addDependency(['ValueSwitchDependency', $switches], 'gcj_from_method');

        $this->set('gcj_from_fixed', [
            'label' => '',
            'elementClass' => 'Hidden',
            'validators[mail]' => 'SimpleEmail',
            'order' => 2400,
        ]);

        $html = Html::create()->h4($this->_('Receiver'));
        $this->set('send_to', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
            'order' => 3100,
        ]);

        $this->set('gcj_to_method', [
            'multiOptions' => $this->commJobRepository->getBulkToOptions(),
            'order' => 3200,
        ]);

        $unselected = ['' => ''];
        $fromMethods = $unselected + $this->commJobRepository->getBulkFromOptions();
        $this->set('gcj_fallback_method', [
            'multiOptions' => $fromMethods,
            'order' => 3300,
        ]);
        $this->set('gcj_fallback_fixed', [
            'validators[mail]' => 'SimpleEmail',
            'order' => 3400,
        ]);

        $this->addDependency('CommJob\\Senderdependency');

        $html = Html::create()->h4($this->_('Survey selection'));
        $this->set('selection', [
            'default' => $html,
            'label' => ' ',
            'elementClass' => 'html',
            'value' => $html,
            'order' => 4100,
        ]);

        $this->set('gcj_id_organization', [
            'label' => $this->_('Organization'),
        ]);
    }

    protected function getRoundDescriptionQuery(): string
    {
        return 'SELECT gro_round_description, gro_round_description FROM gems__rounds WHERE gro_id_track = ? GROUP BY gro_round_description';
    }
}