<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Db\ResultFetcher;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Html;
use Gems\Model\GemsJoinModel;
use Gems\Repository\OrganizationRepository;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Sql\Laminas\LaminasSelectModel;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Action for consent overview
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class TrackOverviewHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Tracker\\Overview\\TableSnippet'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    //protected $indexStartSnippets = array('Generic\\ContentTitleSnippet');

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
        'browse'        => true,
        'onEmpty'       => 'getOnEmptyText',
        'showMenu'      => true,
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected OrganizationRepository $organizationRepository,
        protected readonly MetaModelLoader $metaModelLoader,
        protected readonly ResultFetcher $resultFetcher,
    )
    {
        parent::__construct($responder, $translate, $cache);
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
    protected function createModel(bool $detailed, string $action): DataReaderInterface
    {
        $select = $this->resultFetcher->getSelect();
        $select->from('gems__tracks');

        $dataModel = $this->metaModelLoader->createModel(LaminasSelectModel::class, 'track-overview', $select);
        $metaModel = $dataModel->getMetaModel();

        $organizations = $this->organizationRepository->getOrganizations();

        $metaModel->set('gtr_id_track');
        $metaModel->resetOrder();
        $metaModel->set('gtr_track_name', [
            'label' => $this->_('Track name'),
        ]);
        $metaModel->set('total', [
            'label' => $this->_('Total'),
            'no_text_search' => true,
            'column_expression' => new Expression("(LENGTH(gtr_organizations) - LENGTH(REPLACE(gtr_organizations, '|', ''))-1)")
        ]);

        $sql = "CASE WHEN gtr_organizations LIKE '%%|%s|%%' THEN 1 ELSE 0 END";
        foreach ($organizations as $orgId => $orgName) {
            $metaModel->set('O' . $orgId, [
                'label' => $orgName,
                'tdClass' => 'rightAlign',
                'thClass' => 'rightAlign',
                'no_text_search' => true,
                'column_expression' => new Expression(sprintf($sql, $orgId))
            ]);

            if ($action !== 'export') {
                $metaModel->set('O'. $orgId, [
                    'formatFunction' => [$this, 'formatCheckmark'],
                ]);
            }
        }

         // \MUtil\Model::$verbose = true;

        return $dataModel;
    }

    public function formatCheckmark($value)
    {
        if ($value === 1) {
            return Html::create('span', ['class'=>'checked'])->i(['class' => 'fa fa-check', 'style' => 'color: green;']);
        }
        return null;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->_('track per organization');
    }

    /**
     * Calculated fields can not exist in a where clause.
     *
     * We don't need to search on them with the text filter, so we return
     * an empty array to disable text search.
     *
     * @return array
     */
    public function noTextFilter()
    {
        return [];
    }
}