<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Overview;

use DateTimeImmutable;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentSiblingsButtonRowSnippet;
use Gems\Snippets\Token\PlanSearchSnippet;
use Gems\Snippets\Token\PlanTokenSnippet;
use Gems\Snippets\Tracker\TokenStatusLegenda;
use Gems\Tracker;
use Gems\Tracker\Model\TokenModel;
use Mezzio\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;


/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 22-apr-2015 17:53:02
 */
abstract class TokenSearchHandlerAbstract extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $autofilterParameters = [
        'surveyReturn' => 'setSurveyReturn',
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [
        PlanTokenSnippet::class
    ];

    /**
     * En/disable the checking for answers on load.
     *
     * @var boolean
     */
    protected bool $checkForAnswersOnLoad = true;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        PlanSearchSnippet::class,
        ];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStopSnippets = [
        TokenStatusLegenda::class,
        CurrentSiblingsButtonRowSnippet::class,
        ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected MetaModelLoader $metaModelLoader,
        protected PeriodSelectRepository $periodSelectRepository,
        protected Tracker $tracker,
    ) {
        parent::__construct($responder, $translate);
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
     * @return DataReaderInterface
     */
    public function createModel(bool $detailed, string $action): DataReaderInterface
    {
        if (TokenModel::$useTokenModel) {
            $model = $this->metaModelLoader->createModel(TokenModel::class);
        } else {
            // \MUtil\Model::$verbose = true;
            $model = $this->tracker->getTokenModel();
//        $model->setCreate(false);
        }

        $metaModel = $model->getMetaModel();
        $metaModel->set('gr2o_patient_nr',       [
            'label' => $this->_('Respondent'),
        ]);
        $metaModel->set('gto_round_description', [
            'label' => $this->_('Round / Details'),
        ]);
        $metaModel->set('gto_valid_from',        [
            'label' => $this->_('Valid from'),
        ]);
        $metaModel->set('gto_valid_until',       [
            'label' => $this->_('Valid until'),
        ]);
        $metaModel->set('gto_mail_sent_date',    [
            'label' => $this->_('Contact date'),
        ]);
        $metaModel->set('respondent_name',       [
            'label' => $this->_('Name'),
        ]);

        return $model;
    }

    /**
     * Bulk email action
     */
    public function emailAction()
    {
        $model   = $this->getModel();

        $model->setFilter($this->getSearchFilter(false));

        $sort = array(
            'gr2o_email'          => SORT_ASC,
            'grs_first_name'     => SORT_ASC,
            'grs_surname_prefix' => SORT_ASC,
            'grs_last_name'      => SORT_ASC,
            'gto_valid_from'     => SORT_ASC,
            'gto_round_order'    => SORT_ASC,
            'gsu_survey_name'    => SORT_ASC,
        );

        if ($tokensData = $model->load(true, $sort)) {

            $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

            $params['mailTarget']           = 'token';
            $params['model']                = $model;
            $params['identifier']           = $this->_getIdParam();
            $params['routeAction']          = 'index';
            $params['formTitle']            = sprintf($this->_('Send mail to: %s'), $this->getTopic());
            $params['templateOnly']         = ! $currentUser->hasPrivilege('pr.token.mail.freetext');
            $params['multipleTokenData']    = $tokensData;

            $this->addSnippet('Mail\\TokenBulkMailFormSnippet', $params);
        } else {
            $statusMessenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $statusMessenger->addMessage($this->_('No tokens found.'));
        }
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults(): array
    {
        if (! $this->defaultSearchData) {
            $dateType = $this->metaModelLoader->getDefaultTypeInterface(MetaModelInterface::TYPE_DATE);
            $format   = $dateType->getSetting('dateFormat');
            $today    = (new DateTimeImmutable('today'))->format($format);

            $this->defaultSearchData = array(
                'datefrom'    => $today,
                'dateused'    => '_gto_valid_from gto_valid_until',
                'dateuntil'   => $today,
                'main_filter' => '',
            );
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useRequest = true): array
    {
        $filter = parent::getSearchFilter($useRequest);

        unset($filter['AUTO_SEARCH_TEXT_BUTTON']);

        $where = $this->periodSelectRepository->createPeriodFilter($filter, null, 'Y-m-d H:i:s');
        if ($where) {
            $filter[] = $where;
        }

        if (! isset($filter['gto_id_organization'])) {
            $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
            $filter['gto_id_organization'] = $currentUser->getRespondentOrgFilter();
        }
        $filter['gsu_active']  = 1;

        // When we dit not select a specific status we skip the deleted status
        if (!isset($filter['token_status'])) {
            $filter['grc_success'] = 1;
        }

        if (isset($filter['forgroupid'])) {
            $values = explode('|', $filter['forgroupid']);
            if(count($values) > 1) {
                $groupType = array_shift($values);
                if ('g' == $groupType) {
                    $filter['ggp_id_group'] = $values;
                } elseif ('r' == $groupType) {
                    $filter['gtf_id_field'] = $values;
                }
            }
            unset($filter['forgroupid']);
        }

        if (isset($filter['main_filter'])) {
            switch ($filter['main_filter']) {
                case 'answered':
                    $filter[] = 'gto_completion_time IS NOT NULL';

                case 'hasnomail':
                    $filter[] =
                        "((gr2o_email IS NULL OR gr2o_email = '') AND
                                ggp_member_type = 'respondent' AND (gto_id_relationfield IS NULL OR gto_id_relationfield < 1) AND gr2o_mailable = 1)
                             OR
                             ((grr_email IS NULL OR grr_email = '') AND
                                ggp_member_type = 'respondent' AND gto_id_relationfield > 0 AND grr_mailable = 1)";
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    // Exclude not mailable, we don't want to ask them for email if we are not allowed to use it anyway
                    $filter[] = 'gr2t_mailable > 0';
                    break;

                case 'notmailable':
                    $filter[] = '(((gto_id_relationfield IS NULL OR gto_id_relationfield < 1) AND gr2o_mailable = 0) OR (gto_id_relationfield > 0 AND grr_mailable = 0) OR gr2t_mailable = 0) AND ggp_member_type = \'respondent\'';
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'notmailed':
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'tomail':
                    $filter[] =
                        "(gr2o_email IS NOT NULL AND gr2o_email != '' AND
                                ggp_member_type = 'respondent' AND (gto_id_relationfield IS NULL OR gto_id_relationfield < 1) AND gr2o_mailable = 1)
                              OR
                              (grr_email IS NOT NULL AND grr_email != '' AND
                                ggp_member_type = 'respondent' AND gto_id_relationfield > 0 AND grr_mailable = 1)";
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    // Exclude not mailable
                    $filter[] = 'gr2t_mailable > 0';
                    break;

                case 'toremind':
                    // $filter['can_email'] = 1;
                    $filter[] = 'gto_mail_sent_date < CURRENT_TIMESTAMP';
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                default:
                    break;
            }
            unset($filter['main_filter']);
        }

        return $filter;
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
     * Default overview action
     */
    public function indexAction(): void
    {
        if ($this->checkForAnswersOnLoad) {
            $session = $this->request->getAttribute(SessionInterface::class);
            $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
            $this->tracker->processCompletedTokens(
                $session,
                null,
                $currentUser->getUserId(),
                $currentUser->getCurrentOrganizationId(),
                true
            );
        }

        parent::indexAction();
    }
}
