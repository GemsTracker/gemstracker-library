<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\AccessRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Survey;
use Gems\Tracker\Model\StandardTokenModel;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 23-apr-2015 12:34:48
 */
class InsertSurveySnippet extends ModelFormSnippetAbstract
{
    protected bool $buttonDisabled = false;


    /**
     *
     * @var array The fields from formData to copy to the token when creating it
     */
    protected array $copyFields = [
        'gto_id_round',
        'gto_valid_from',
        'gto_valid_from_manual',
        'gto_valid_until',
        'gto_valid_until_manual',
        'gto_comment',
        'gto_id_relationfield',
    ];

    /**
     * True when the form should edit a new model item.
     *
     * @var boolean
     */
    protected $createData = true;

    protected int $currentUserId;

    /**
     *
     * @var int
     */
    protected int $defaultRound = 10;

    /**
     * The items on the form - in the order of display
     *
     * @var array
     */
    protected array $formItems = [
        'gto_id_respondent',
        'gr2o_patient_nr',
        'respondent_name',
        'gto_id_organization',
        'gto_id_survey',
        'ggp_name',
        'gto_id_relationfield',
        'gto_id_track',
        'gto_round_order',
        'gto_valid_from_manual',
        'gto_valid_from',
        'gto_valid_until_manual',
        'gto_valid_until',
        'gto_comment',
    ];

    /**
     * @var bool Allow selection of more than one survey
     */
    protected bool $insertMultipleSurveys = true;

    /**
     * Required
     *
     * @var array of \Gems\Tracker\RespondentTrack Respondent Track
     */
    protected array $respondentTracks = [];

    /**
     *
     * @var array of surveyId => Survey
     */
    protected array $surveys = [];

    /**
     *
     * @var array id => label
     */
    protected ?array $surveyList = null;

    /**
     * The newly create token
     *
     * @var array of \Gems\Tracker\Token
     */
    protected array $tokens = [];

    /**
     *
     * @var array of respondent track id => description
     */
    protected array $tracksList;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected Tracker $tracker,
        CurrentUserRepository $currentUserRepository,
        RespondentRepository $respondentRepository,
        protected TrackDataRepository $trackDataRepository,
        protected AccessRepository $accessRepository,
        protected ResultFetcher $resultFetcher,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);

        if ($this->insertMultipleSurveys) {
            $this->saveLabel = $this->_('Insert survey(s)');
        } else {
            $this->saveLabel = $this->_('Insert survey');
        }

        $this->currentUserId = $currentUserRepository->getCurrentUserId();
        $this->respondent = $respondentRepository->getRespondent(
            $requestInfo->getParam(\MUtil\Model::REQUEST_ID1),
            $requestInfo->getParam(\MUtil\Model::REQUEST_ID2),
        );

        $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl('respondent.tracks.index', $this->requestInfo->getRequestMatchedParams());
    }

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed $message_args Can be an array or multiple argemuents. Each sub element is a single message string
     * @return self (continuation pattern)
     */
    public function addMessageInvalid($message_args)
    {
        $this->buttonDisabled = true;

        parent::addMessage(func_get_args());
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        $model = $this->tracker->getTokenModel();

        if ($model instanceof StandardTokenModel) {
            if ($this->createData) {
                $model->applyInsertionFormatting();
            }
        }

        // Valid from can not be calculated for inserted rounds, and should always be manual
        $model->set('gto_valid_from_manual', 'elementClass', 'Hidden', 'default', '1');

        if (! $this->surveyList) {
            $this->surveyList = $this->trackDataRepository->getInsertableSurveys($this->respondent->getOrganizationId());
        }

        $model->set('gto_id_survey', [
            'label' => $this->_('Suvey to insert'),
            'autosubmit' => true,
            // 'elementClass' set in loadSurvey
            'multiOptions' => $this->surveyList,
            ]);
        $model->set('gto_id_track', [
            'label' => $this->_('Existing track'),
            'autosubmit' => true,
            'elementClass' => 'Select',
            //'multiOptions' set in loadTrackSettings
            ]);
        $model->set('gto_round_order', 'label', $this->_('In round'),
                'elementClass', 'Select',
                //'multiOptions' set in loadRoundSettings
                'required', true
                );
        $model->set('gto_valid_from',
                'required', true
                );

        return $model;
    }
    
    /**
     * Get a select with the fields:
     *  - round_order: The gto_round_order to use for this round
     *  - has_group: True when has surveys for same group as current survey
     *  - group_answered: True when has answers for same group as current survey
     *  - any_answered: True when has answers for any survey
     *  - round_description: The gto_round_description for the round
     *
     * @return Select or you can return a nested array containing said output/
     */
    protected function getRoundSelect()
    {
        $select = $this->resultFetcher->getSelect('gems__tokens');
        $select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', [])
            ->join('gems__surveys', 'gto_id_survey = gsu_id_survey', [])
            ->group(['gto_round_description']);


        $groupIds = implode(', ', $this->getGroupIds());
        if ($groupIds) {
            $select->columns([
                'round_description' => 'gto_round_description',
                // Round order is maximum for the survey's group unless this round had no surveys of the same group
                'round_order'    => new Expression(
                        "COALESCE(
                            MAX(CASE WHEN gsu_id_primary_group IN ($groupIds) THEN gto_round_order ELSE NULL END),
                            MAX(gto_round_order)
                            ) + 1"
                        ),
                'has_group'      => new Expression(
                        "SUM(CASE WHEN gsu_id_primary_group IN ($groupIds) THEN 1 ELSE 0 END)"
                        ),
                'group_answered' => new Expression(
                        "SUM(CASE WHEN gto_completion_time IS NOT NULL AND gsu_id_primary_group IN ($groupIds)
                            THEN 1
                            ELSE 0
                            END)"
                        ),
                'any_answered'   => new Expression(
                        "SUM(CASE WHEN gto_completion_time IS NOT NULL THEN 1 ELSE 0 END)"
                        ),
            ]);
        } else {
            $select->columns([
                'round_description' => 'gto_round_description',
                'round_order'    => new Expression("MAX(gto_round_order) + 1"),
                'has_group'      => new Expression("0"),
                'group_answered' => new Expression("0"),
                'any_answered'   => new Expression(
                        "SUM(CASE WHEN gto_completion_time IS NOT NULL THEN 1 ELSE 0 END)"
                        ),
            ]);
        }

        if (isset($this->formData['gto_id_track'])) {
            $select->where([
                'gto_id_respondent_track' => $this->formData['gto_id_track'],
            ]);
        } else {
            $select->where->equalTo(1, 0);
        }

        $select->order(['round_order']);
        
        return $select;
    }

    /**
     * @param int $changed
     * @return string
     */
    public function getChangedMessage($changed): string
    {
        return sprintf($this->plural('%d survey inserted', '%d surveys inserted', $changed), $changed);
    }

    /**
     * @return array groupId => groupId for all selected surveys
     */
    public function getGroupIds()
    {
        $groupIds = [];
        if ($this->surveys) {
            foreach ($this->surveys as $survey) {
                if ($survey instanceof Survey) {
                    $groupIds[$survey->getGroupId()] = $survey->getGroupId();
                }
            }
        }
        return $groupIds;
    }

    /**
     * Get the list of rounds and set the default
     *
     * @return array [roundInsertNr => RoundDescription
     */
    protected function getRoundsListAndSetDefault()
    {
        $model  = $this->getModel();
        $output = array();
        $select = $this->getRoundSelect();

        if ($select instanceof Select) {
            $rows = $this->resultFetcher->fetchAll($select);
        } else {
            $rows = $select;
        }

        if ($rows) {

            // Initial values
            $maxAnswered      = 0;
            $maxGroupAnswered = 0;
            $minGroup         = -1;

            foreach ($rows as $row) {
                $output[$row['round_order']] = $row['round_description'];

                if ($row['has_group']) {
                    if (-1 === $minGroup) {
                        $minGroup = $row['round_order'];
                    }
                    if ($row['group_answered']) {
                        $maxGroupAnswered = $row['round_order'];
                    }
                }
                if ($row['any_answered']) {
                    $maxAnswered = $row['round_order'];
                }
            }
            if ($maxGroupAnswered) {
                $this->defaultRound = $maxGroupAnswered;
                $model->set('gto_round_order', 'description', sprintf(
                        $this->_('The last round containing answers for surveys in the same user group is "%s".'),
                        $output[$this->defaultRound]
                        ));

            } elseif ($maxAnswered) {
                $this->defaultRound = $maxAnswered;
                $model->set('gto_round_order', 'description', sprintf(
                        $this->_('The last round containing answers is "%s".'),
                        $output[$this->defaultRound]
                        ));

            } elseif (-1 !== $minGroup) {
                $this->defaultRound = $minGroup;
                $model->set('gto_round_order', 'description', sprintf(
                        $this->_('No survey has been answered, the first round with surveys in the same user group is "%s".'),
                        $output[$this->defaultRound]
                        ));

            } else {
                reset($output);
                $this->defaultRound = key($output);
                $model->set('gto_round_order',
                        'description', $this->_('No surveys have answers, nor are any in the same user group.')
                        );
            }

        } else {
            $output[10] = $this->_('Added survey');
            $this->defaultRound = 10;
            $model->set('gto_round_order',
                    'description', $this->_('No current rounds available.')
                    );
        }

        return $output;
    }
    
    /**
     * When there is no track to add to we return false
     * 
     * @return boolean
     */
    public function hasHtmlOutput(): bool {
        $this->initTracks();
        $canDo = count($this->tracksList) > 0;
        if ($canDo === false) { 
            $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl('respondent.show', $this->requestInfo->getRequestMatchedParams());
        }
        return $canDo && parent::hasHtmlOutput();
    }

    /**
     * @param \Gems\Tracker\RespondentTrack $respondentTrack
     * @return bool
     */
    protected function includeTrack(\Gems\Tracker\RespondentTrack $respondentTrack)
    {
        return $respondentTrack->getReceptionCode()->isSuccess(); 
    }
    
    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems(MetaModelInterface $metaModel)
    {
        if (is_null($this->_items)) {
            $this->_items = array_merge(
                    $this->formItems,
                    $metaModel->getMeta(\MUtil\Model\Type\ChangeTracker::HIDDEN_FIELDS, array())
                    );
            if (! $this->createData) {
                array_unshift($this->_items, 'gto_id_token');
            }
        }
    }

    /**
     * @throws \Gems\Exception
     */
    protected function initTracks()
    {
        $this->respondentTracks = $this->tracker->getRespondentTracks(
            $this->respondent->getId(), 
            $this->respondent->getOrganizationId(), 
            'gr2t_start_date DESC'  // Descending order, last added track comes first
        );
        $this->tracksList       = [];
        
        foreach ($this->respondentTracks as $respTrack) {
            if ($respTrack instanceof \Gems\Tracker\RespondentTrack) {
                if ($this->includeTrack($respTrack)) {
                    $this->tracksList[$respTrack->getRespondentTrackId()] = substr(sprintf(
                                    $this->_('%s - %s'),
                                    $respTrack->getTrackEngine()->getTrackName(),
                                    $respTrack->getFieldsInfo()
                            ), 0, 100);
                }
            }
        }
        if (! $this->tracksList) {
            $this->addMessageInvalid($this->_('Survey insertion impossible: respondent has no track!'));
        }
    }

    /**
     * @return bool True when any selected survey is not taken by staff
     */
    protected function isAnySurveyTakenByRespondents()
    {
        foreach ($this->surveys as $survey) {
            if ($survey instanceof Survey) {
                if (! $survey->isTakenByStaff()) {
                    return true;
                }
            }
        } 
        return false;
    }    

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        if ($this->createData && (! $this->requestInfo->isPost())) {
            $surveyId = $this->requestInfo->getParam(\Gems\Model::SURVEY_ID);
            if ($surveyId) {
                if ($this->insertMultipleSurveys) {
                    $surveyIds = [$surveyId];
                } else {
                    $surveyIds = $surveyId;
                }
            } else {
                if ($this->insertMultipleSurveys) {
                    $surveyIds = [];
                } else {
                    $surveyIds = null;
                }
            }
            
            $this->formData = array(
                'gr2o_patient_nr'        => $this->respondent->getPatientNumber(),
                'gto_id_organization'    => $this->respondent->getOrganizationId(),
                'gto_id_respondent'      => $this->respondent->getId(),
                'respondent_name'        => $this->respondent->getName(),
                'gto_id_survey'          => $surveyIds,
                'gto_id_track'           => $this->requestInfo->getParam(\Gems\Model::TRACK_ID),
                'gto_valid_from_manual'  => 1,
                'gto_valid_from'         => new \DateTimeImmutable(),
                'gto_valid_until_manual' => 0,
                'gto_valid_until'        => null, // Set in loadSurvey
                );

            $output = $this->getModel()->processAfterLoad(array($this->formData), $this->createData, false);
            $this->formData = reset($output);
        } else {
            parent::loadFormData();
        }

        $this->loadSurvey();
        $this->loadTrackSettings();
        $this->loadRoundSettings();

        // \MUtil\EchoOut\EchoOut::track($this->formData);
        return $this->formData;
    }

    /**
     * Load the settings for the round
     */
    protected function loadRoundSettings()
    {
        $rounds = $this->getRoundsListAndSetDefault();
        $model  = $this->getModel();
        $model->set('gto_round_order', 'multiOptions', $rounds, 'size', count($rounds));

        if (count($rounds) === 1) {
            $model->set('gto_round_order', 'elementClass', 'Exhibitor');
        }

        if (! isset($this->formData['gto_round_order'], $rounds[$this->formData['gto_round_order']])) {
            $this->formData['gto_round_order'] = $this->defaultRound;
        }
        if (! isset($rounds[$this->formData['gto_round_order']])) {
            reset($rounds);
            $this->formData['gto_round_order'] = key($rounds);
        }
    }

    /**
     * Load the survey object and use it
     */
    protected function loadSurvey()
    {
        if (! $this->surveyList) {
            $this->addMessageInvalid($this->_('Survey insertion impossible: no insertable survey exists!'));
        }

        $model = $this->getModel();
        if (count($this->surveyList) === 1) {
            $model->set('gto_id_survey', 'elementClass', 'Exhibitor');

            reset($this->surveyList);
            $this->formData['gto_id_survey'] = key($this->surveyList);
        } elseif ($this->insertMultipleSurveys) {
            $model->set('gto_id_survey', 'elementClass', 'MultiCheckbox', 'required', 'required');
        }

        if (isset($this->formData['gto_id_survey'])) {
            foreach ((array) $this->formData['gto_id_survey'] as $surveyId) {
                $this->surveys[$surveyId] = $this->tracker->getSurvey($surveyId);
            }
            
            $groups = array_intersect_key($this->accessRepository->getGroups(), $this->getGroupIds());
            if ($groups) {
                $this->formData['ggp_name'] = implode($this->_(', '), $groups);
            }            

            if (!(isset($this->formData['gto_valid_until_manual']) && $this->formData['gto_valid_until_manual'])) {
                foreach ($this->surveys as $survey) {
                    if ($survey instanceof Survey) {
                        // Just use the date of the first select survey
                        // No theory about this, will usually be 6 months
                        $this->formData['gto_valid_until'] = $survey->getInsertDateUntil($this->formData['gto_valid_from']);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Load the settings for the survey
     */
    protected function loadTrackSettings()
    {
        $respTracks = $this->respondentTracks;
        
        if (! isset($this->formData['gto_id_track'])) {
            reset($this->tracksList);
            $this->formData['gto_id_track'] = key($this->tracksList);
        }
        
        asort($this->tracksList);
        
        $model = $this->getModel();
        $model->set('gto_id_track', 'multiOptions', $this->tracksList);
        if (count($this->tracksList) === 1) {
            $model->set('gto_id_track', 'elementClass', 'Exhibitor');
        }

        if (isset($this->formData['gto_id_track'], $respTracks[$this->formData['gto_id_track']]) && (int) $this->formData['gto_id_track'] > 0) {
            $this->respondentTrack = $respTracks[$this->formData['gto_id_track']];

            // Add relation field when survey is not for staff
            if ($this->isAnySurveyTakenByRespondents()) {
                
                $engine = $this->respondentTrack->getTrackEngine();
                $empty  = array('-1' => $this->_('Patient'));
                $relations = $empty + $engine->getRespondentRelationFields();
                $model->set('gto_id_relationfield', 'label', $this->_('Fill out by'),
                    'elementClass', (1 == count($relations) ? 'Exhibitor' : 'Select'),
                    'multiOptions', $relations,
                    'required', true
                );
                
                if (! isset($this->formData['gto_id_relationfield'])) {
                    reset($relations);
                    $this->formData['gto_id_relationfield'] = key($relations);
                }
            }
        }
    }

    protected function processForm()
    {
        $result = parent::processForm();
        if ($this->buttonDisabled && $this->_saveButton instanceof \Zend_Form_Element_Submit) {
            $this->_saveButton->setAttrib('disabled', 'disabled');
        }

        return $result;
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
        $model = $this->getModel();

        $tokenData  = array();

        foreach ($this->copyFields as $name) {
            if (array_key_exists($name, $this->formData)) {
                if ($model->hasOnSave($name)) {
                    $tokenData[$name] = $model->getOnSave($this->formData[$name], $this->createData, $name, $this->formData);
                } elseif ('' === $this->formData[$name]) {
                    $tokenData[$name] = null;
                } else {
                    $tokenData[$name] = $this->formData[$name];
                }
            } else {
                $tokenData[$name] = null;
            }
        }
        $changed  = 0;
        $relation = isset($tokenData['gto_id_relationfield']) ? $tokenData['gto_id_relationfield'] : null;
        $rounds   = $model->get('gto_round_order', 'multiOptions');
        $tokenData['gto_id_round']          = '0';
        $tokenData['gto_round_order']       = $this->formData['gto_round_order'];
        $tokenData['gto_round_description'] = $rounds[$this->formData['gto_round_order']];

        foreach ((array) $this->formData['gto_id_survey'] as $surveyId) {
            $survey = $this->tracker->getSurvey($surveyId);
            
            if ($survey instanceof Survey) {
                // We may have a mix of relation and non/relation fields
                if ($survey->isTakenByStaff()) {
                    unset($tokenData['gto_id_relationfield']);
                } else {
                    $tokenData['gto_id_relationfield'] = $relation;
                }
                $this->tokens[] = $this->respondentTrack->addSurveyToTrack($surveyId, $tokenData, $this->currentUserId);
                $tokenData['gto_round_order']++;
                
                $changed++;
            }
        }


        // Communicate with the user
        return $changed;
    }
}
