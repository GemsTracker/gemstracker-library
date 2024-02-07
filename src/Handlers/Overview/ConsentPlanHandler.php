<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Overview;

use Gems\Db\ResultFetcher;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\MetaModelLoader;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentSiblingsButtonRowSnippet;
use Gems\Snippets\ModelTableSnippet;
use Laminas\Db\Sql\Expression;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Sql\Laminas\LaminasSelectModel;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Action for consent overview
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class ConsentPlanHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

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
        'addCurrentSiblings' => false,
        ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var array String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        ];

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $showParameters = [
        'browse'        => false,
        'onEmpty'       => 'getOnEmptyText',
        'showMenu'      => false,
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        ];

    /**
     * The parameters used for the autofilter action.
     *
     * Disable row counting to speed up the page.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $autofilterParameters = [
        'browse'    => false,
    ];

    /**
     * The snippets used for the show action.
     *
     * @var array String or array of snippets name
     */
    protected array $showSnippets = [
        ModelTableSnippet::class,
        CurrentSiblingsButtonRowSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        CurrentUserRepository $currentUserRepository,
        protected readonly MetaModelLoader $metaModelLoader,
        protected readonly ResultFetcher $resultFetcher,
        protected ReceptionCodeRepository $receptionCodeRepository,
        protected RespondentRepository $respondentRepository,
    ) {
        parent::__construct($responder, $translate, $cache);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * @inheritdoc 
     */
    protected function createModel($detailed, $action): DataReaderInterface
    {
        // Export all
        if ('export' === $action) {
            $detailed = true;
        }

        $fixed = ['gr2o_id_organization'];
        $fields = array_combine($fixed, $fixed);
        if ($detailed) {
            $year  = $this->_('Year');
            $month = $this->_('Month');
            $fields[$year]  = new Expression("YEAR(gr2o_created)");
            $fields[$month] = new Expression("MONTH(gr2o_created)");
        }

        $consents = $this->respondentRepository->getRespondentConsents();
        $deleteds = $this->receptionCodeRepository->getRespondentDeletionCodes();
        $sql      = "SUM(CASE WHEN grc_success = 1 AND gr2o_consent = '%s' THEN 1 ELSE 0 END)";
        foreach ($consents as $consent => $translated) {
            $fields[$consent] = new Expression(sprintf($sql, $consent));
        }
        $fields[$this->_('Total OK')] = new Expression("SUM(CASE WHEN grc_success = 1 THEN 1 ELSE 0 END)");

        $sql      = "SUM(CASE WHEN gr2o_reception_code = '%s' THEN 1 ELSE 0 END)";
        foreach ($deleteds as $code => $translated) {
            $fields[$code] = new Expression(sprintf($sql, $code));
        }
        $fields[$this->_('Dropped')] = new Expression("SUM(CASE WHEN grc_success = 0 THEN 1 ELSE 0 END)");
        $fields[$this->_('Total')]   = new Expression("COUNT(*)");

        $select = $this->resultFetcher->getSelect();
        $select->from('gems__respondent2org')
            ->columns($fields)
            ->join('gems__reception_codes',
                'gr2o_reception_code = grc_id_reception_code',
                [])
            ->join('gems__organizations',
                'gr2o_id_organization = gor_id_organization',
                ['gor_name', 'gor_id_organization']);
        $select->group(['gor_name', 'gor_id_organization']);

        if ($detailed) {
            $select->group([$fields[$year], $fields[$month]]);
        }

        $dataModel = $this->metaModelLoader->createModel(LaminasSelectModel::class, 'consent-plan', $select);
        $metaModel = $dataModel->getMetaModel();
        $metaModel->setKeys(['gor_id_organization']);
        $metaModel->resetOrder();
        $metaModel->set('gor_name', [
            'label' => $this->_('Organization'),
        ]);
        foreach ($fields as $field => $expr) {
            if ($field != $expr) {
                $metaModel->set($field, [
                    'label' => $field,
                    'tdClass' => 'rightAlign',
                    'thClass' => 'rightAlign'
                ]);
            }
        }
        foreach ($deleteds as $code => $translated) {
            $metaModel->set($code, [
                'label' => $translated,
                'tdClass' => 'rightAlign smallTime',
                'thClass' => 'rightAlign smallTime',
            ]);
        }
        foreach ([$this->_('Total OK'), $this->_('Dropped'), $this->_('Total')] as $name) {
            $metaModel->set($name, [
                'itemDisplay' => Html::create('strong'),
                'tableHeaderDisplay' => Html::create('em'),
                'tdClass' => 'rightAlign selectedColumn',
                'thClass' => 'rightAlign selectedColumn'
            ]);
        }

        if ($detailed) {
            // $model->set($month, 'formatFunction', []);
        }

        // Only show organizations the user is allowed to see
        $allowed = $this->currentUser->getAllowedOrganizations();
        $this->autofilterParameters['extraFilter']['gr2o_id_organization'] = array_keys($allowed);

        return $dataModel;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->_('consent per organization');
    }

}
