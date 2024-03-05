<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Batch\BatchRunnerLoader;
use Gems\Exception;
use Gems\Handlers\Overview\TokenSearchHandlerAbstract;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Respondent\TokenEmailSnippet;
use Gems\Tracker;
use Gems\Tracker\Token;
use Mezzio\Session\SessionMiddleware;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Ra\Ra;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class TokenHandler extends TokenSearchHandlerAbstract
{
    use GetRespondentTrait;

    protected array $answerParameters = [];

    /**
     * The parameters used for the answer export action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $answerExportParameters = [
        'formTitle' => 'getTokenTitle',
        'hideGroup' => true,
    ];

    protected array $answerExportSnippets = ['Export\\RespondentExportSnippet'];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Token\\RespondentPlanTokenSnippet'];

    protected array $checkTokenParameters = [];

    protected array $checkTokenSnippets = [
        'Token\\CheckTokenEvents',
        'Survey\\SurveyQuestionsSnippet'
    ];

    /**
     * The parameters used for the correct action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $correctParameters = [
        'csrfName'           => 'getCsrfTokenName',
        'csrfToken'          => 'getCsrfToken',
        'fixedReceptionCode' => 'redo',
        'formTitle'          => 'getCorrectTokenTitle',
    ];

    /**
     * The default parameters used for any token action like answers or sho0w
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $defaultTokenParameters = [
        'model'      => null,
        'respondent' => null,
        'token'      => 'getToken',
        'tokenId'    => 'getTokenId',
    ];

    protected array $deleteParameters = [
        'formTitle'     => null,
    ];

    protected array $emailParameters = [
        'formTitle'    => 'getEmailTokenTitle',
        'identifier'   => '_getIdParam',
        'mailTarget'   => 'token',
        // 'model'        => 'getModel',
        'routeAction'  => 'show',
        'dataResource' => 'emailTokenModel',
        'dataEndpoint' => 'respondent/email-token',
    ];

    /**
     * Snippets used for emailing
     *
     * @var mixed String or array of snippets name
     */
    protected array $emailSnippets = [
        ContentTitleSnippet::class,
        TokenEmailSnippet::class,
    ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Token\\TokenSearchSnippet'];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStopSnippets = ['Tracker\\TokenStatusLegenda'];

    protected array $questionsParameters = [
        'surveyId' => 'getSurveyId',
    ];

    protected array $questionsSnippets = [
        'Survey\\SurveyQuestionsSnippet',
        'Tracker\\Buttons\\TokenActionButtonRow',
    ];

    protected static array $parameterMaps = [
        Model::REQUEST_ID1 => 'gr2o_patient_nr',
        Model::REQUEST_ID2 => 'gto_id_organization',
        Model::RESPONDENT_TRACK => 'gto_id_respondent_track',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        MetaModelLoader $metaModelLoader,
        PeriodSelectRepository $periodSelectRepository,
        Tracker $tracker,
        CurrentUserRepository $currentUserRepository,
        protected RespondentRepository $respondentRepository,
        protected OrganizationRepository $organizationRepository,
        protected BatchRunnerLoader $batchRunnerLoader,
    ) {
        parent::__construct($responder, $translate, $cache, $metaModelLoader, $currentUserRepository, $periodSelectRepository, $tracker);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    public function answerAction()
    {
        $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

        $token = $this->getToken();
        if (! $token->isViewable()) {
            throw new \Gems\Exception(
                sprintf($this->_('Inaccessible or unknown token %s'), strtoupper($token->getTokenId())),
                403, null,
                sprintf($this->_('Access to this token is not allowed for current role: %s.'), $currentUser->getRole()));
        }

        $snippetNames = $token->getAnswerSnippetNames();

        if ($snippetNames) {
            //$this->setTitle(sprintf($this->_('Token answers: %s'), strtoupper($token->getTokenId())));

            $params = $this->_processParameters($this->answerParameters + $this->defaultTokenParameters);

            list($snippets, $snippetParams) = Ra::keySplit($snippetNames);

            if ($snippetParams) {
                $params += $snippetParams;
            }

            $this->addSnippets($snippets, $params);
        }
    }

    /**
     * Export a single token
     */
    public function answerExportAction()
    {
        if ($this->answerExportSnippets) {
            $params = $this->_processParameters($this->answerExportParameters + $this->defaultTokenParameters);

            $this->addSnippets($this->answerExportSnippets, $params);
        }
    }

    public function checkTokenAction()
    {
        if ($this->checkTokenSnippets) {
            $params = $this->_processParameters($this->checkTokenParameters + $this->defaultTokenParameters);

            $this->addSnippets($this->checkTokenSnippets, $params);
        }
    }

    public function checkTokenAnswersAction()
    {
        $token       = $this->getToken();
        $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        $batch = $this->tracker->recalculateTokens(
            $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            'answersCheckToken__' . $token->getTokenId(),
            $currentUser->getUserId(),
            ['gto_id_token' => $token->getTokenId()]
        );
        $batch->setBaseUrl($this->requestInfo->getBasePath());

        $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
        $batchRunner->setTitle(sprintf(
            $this->_("Checking the token %s for answers."),
            $token->getTokenId()
        ));

        $batchRunner->setJobInfo([
            $this->_('This task checks one token for answers.'),
        ]);

        return $batchRunner->getResponse($this->request);
    }

    /**
     * Action for correcting answers
     */
    public function correctAction()
    {
        $this->deleteParameters = $this->correctParameters + $this->deleteParameters;

        $this->deleteAction();
    }

    /**
     * Delete a single token
     */
    public function deleteAction(): void
    {
        $this->deleteParameters = $this->deleteParameters + $this->defaultTokenParameters;
        $this->deleteSnippets   = $this->getToken()->getDeleteSnippetNames();
        $this->deleteParameters['requestUndelete'] = false;

        parent::deleteAction();
    }

    /**
     * Edit single token
     */
    public function editAction(): void
    {
        $this->editParameters      = $this->editParameters + $this->defaultTokenParameters;
        $this->createEditSnippets  = $this->getToken()->getEditSnippetNames();

        parent::editAction();
    }

    /**
     * Email the user
     */
    public function emailAction()
    {
        if ($this->emailSnippets) {
            $params = $this->_processParameters($this->emailParameters + $this->defaultTokenParameters);
            $params['submitLabel'] = $this->translate->_('Send email');
            $params['contentTitle'] = $this->translate->_('Send email');

            $this->addSnippets($this->emailSnippets, $params);
        }
    }

    protected function getCorrectTokenTitle()
    {
        $token = $this->getToken();

        return sprintf(
            $this->_('Correct answers for survey %s, round %s'),
            $token->getSurveyName(),
            $token->getRoundDescription()
        );
    }

    protected function getEmailTokenTitle()
    {
        $token      = $this->getToken();
        $respondent = $token->getRespondent();

        // Set params
        return sprintf(
            $this->_('Send mail to %s respondent nr %s for token %s'),
            $token->getEmail(),          // When using relations, this is the right email address
            $respondent->getPatientNumber(),
            $token->getTokenId()
        );
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return sprintf($this->_('Surveys assigned to respondent %s'), $this->request->getAttribute(Model::REQUEST_ID1));
    }

    /**
     * Get the data to use for searching: the values passed in the request + any defaults
     * used in the search form (or any other search request mechanism).
     *
     * It does not return the actual filter used in the query.
     *
     * @see getSearchFilter()
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array
     */
    public function getSearchData(bool $useRequest = true): array
    {
        $data = parent::getSearchData($useRequest);
        // Survey action data
        $data['gto_id_respondent']   = $this->getRespondentId();
        if (isset($data['gto_id_organization'])) {
            $this->currentUserRepository->assertAccessToOrganizationId($data['gto_id_organization']);
        } else {
            $data['gto_id_organization'] = array_keys($this->currentUser->getRespondentOrganizations());
        }

        return $data;
    }

    public function getToken()
    {
        static $token;

        if ($token instanceof Token) {
            return $token;
        }

        $token   = null;
        $tokenId = $this->getTokenId();

        $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

        if ($tokenId) {
            $token = $this->tracker->getToken($tokenId);
        }
        if ($token && $token->exists) {
            if (! array_key_exists($token->getOrganizationId(), $currentUser->getAllowedOrganizations())) {
                throw new Exception(
                    $this->_('Inaccessible or unknown organization'),
                    403, null,
                    sprintf($this->_('Access to this page is not allowed for current role: %s.'), $currentUser->getRole()));
            }

            return $token;
        }

        throw new Exception($this->_('No existing token specified!'));
    }

    /**
     * Retrieve the token ID
     *
     * @return string
     */
    public function getTokenId()
    {
        return $this->_getIdParam();
    }

    protected function getTokenTitle()
    {
        $token      = $this->getToken();
        $respondent = $token->getRespondent();

        // Set params
        return sprintf(
            $this->_('Token %s in round "%s" in track "%s" for respondent nr %s: %s'),
            $token->getTokenId(),
            $token->getRoundDescription(),
            $token->getTrackName(),
            $respondent->getPatientNumber(),
            $respondent->getName()
        );
    }

   /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('token', 'tokens', $count);
    }

    /**
     * Shows the questions in a survey
     */
    public function questionsAction()
    {
        if (!$this->getTokenId()) {
            $params = $this->_processParameters($this->questionsParameters);
        } else {
            $params = $this->_processParameters($this->questionsParameters + $this->defaultTokenParameters);
        }
        if ($this->questionsSnippets) {
            $this->addSnippets($this->questionsSnippets, $params);
        }
    }

    /**
     * Show a single token, mind you: it can be a SingleSurveyTrack
     */
    public function showAction(): void
    {
        $this->showParameters = $this->showParameters + $this->defaultTokenParameters;
        $this->showSnippets   = $this->getToken()->getShowSnippetNames();

        parent::showAction();
    }

    /**
     * Delete a single token
     */
    public function undeleteAction()
    {
        $this->deleteParameters = $this->deleteParameters + $this->defaultTokenParameters;
        $this->deleteSnippets   = $this->getToken()->getDeleteSnippetNames();
        $this->deleteParameters['requestUndelete'] = true;

        parent::deleteAction();
    }
}
