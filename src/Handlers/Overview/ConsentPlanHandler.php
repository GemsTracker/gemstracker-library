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
use Gems\Handlers\GemsHandler;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\Generic\CurrentSiblingsButtonRowSnippet;
use Gems\Snippets\ModelTableSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAsTableAction;
use Laminas\Db\Sql\Expression;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Sql\Laminas\LaminasSelectModel;
use Zalt\SnippetsActions\Browse\BrowseTableAction;
use Zalt\SnippetsActions\SnippetActionInterface;
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
class ConsentPlanHandler extends GemsHandler
{

    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'show'       => ShowAsTableAction::class,
    ];

    protected DataReaderInterface|null $model = null;

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
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly ResultFetcher $resultFetcher,
        protected ReceptionCodeRepository $receptionCodeRepository,
        protected RespondentRepository $respondentRepository,
        protected readonly CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    /**
     * @inheritdoc
     */
    protected function createModel(SnippetActionInterface $action): DataReaderInterface
    {
        $fixed = ['gr2o_id_organization'];
        $fields = array_combine($fixed, $fixed);
        if ($action->isDetailed()) {
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
        $fields[$this->_('Totaal OK')] = new Expression("SUM(CASE WHEN grc_success = 1 THEN 1 ELSE 0 END)");

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

        if ($action->isDetailed()) {
            $select->group([$fields[$year], $fields[$month]]);
        }

        $dataModel = $this->metaModelLoader->createModel(LaminasSelectModel::class, 'consent-plan', $select);
        $metaModel = $dataModel->getMetaModel();
        $metaModel->setKeys(['gr2o_id_organization']);
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

        if ($action->isDetailed()) {
            // $model->set($month, 'formatFunction', []);
        }

        return $dataModel;
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        if (!$this->model) {
            $this->model = $this->createModel($action);
        }
        return $this->model;
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

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);
        if ($action instanceof BrowseTableAction) {
            $action->extraFilter['gr2o_id_organization'] = array_keys($this->currentUserRepository->getCurrentUser()->getAllowedOrganizations());
            $action->browse = false;
        }
        if ($action instanceof ShowAsTableAction) {
            $action->extraFilter['gr2o_id_organization'] = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID);
            $action->setSnippets($this->showSnippets);
            $action->menuShowRoutes = [];
        }
    }

}
