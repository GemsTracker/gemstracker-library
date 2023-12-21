<?php

namespace Gems\Handlers\Overview;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TokenRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentSiblingsButtonRowSnippet;
use Gems\Snippets\Tracker\Compliance\ComplianceLegenda;
use Gems\Snippets\Tracker\Compliance\ComplianceSearchFormSnippet;
use Gems\Snippets\Tracker\Compliance\ComplianceTableSnippet;
use Gems\Snippets\Tracker\TokenStatusLegenda;
use Gems\User\Mask\MaskRepository;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Having;
use Laminas\Db\Sql\Predicate\Expression as PredicateExpression;
use Laminas\Db\Sql\Select;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Sql\Laminas\LaminasSelectModel;
use Zalt\Model\Transform\CrossTabTransformer;
use Zalt\Model\Transform\JoinTransformer;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class ComplianceHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    protected array $autofilterParameters = [
        'extraFilter' => ['grc_success' => 1],
    ];
    
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [
        ComplianceTableSnippet::class,
    ];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        ComplianceSearchFormSnippet::class,
    ];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStopSnippets = [
        CurrentSiblingsButtonRowSnippet::class,
        TokenStatusLegenda::class,
        ComplianceLegenda::class,
        ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected Adapter $laminasDb,
        CurrentUserRepository $currentUserRepository,
        protected MaskRepository $maskRepository,
        protected MetaModelLoader $metaModelLoader,
        protected PeriodSelectRepository $periodSelectRepository,
        protected ResultFetcher $resultFetcher,
        protected TokenRepository $tokenRepository,
        protected TrackDataRepository $trackDataRepository,
    )
    {
        parent::__construct($responder, $translate, $cache);

        $this->currentUser = $currentUserRepository->getCurrentUser();
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
    public function createModel($detailed, $action): DataReaderInterface
    {
        $model = new \Gems\Model\MaskedModel('resptrack' , 'gems__respondent2track');
        $model->setMaskRepository($this->maskRepository);

        $model->addTable('gems__respondent2org', array(
            'gr2t_id_user' => 'gr2o_id_user',
            'gr2t_id_organization' => 'gr2o_id_organization'
            ));
        $model->addTable('gems__respondents', array('gr2o_id_user' => 'grs_id_user'));
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__reception_codes', array('gr2t_reception_code' => 'grc_id_reception_code'));

        $model->resetOrder();
        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));
        $model->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');

        if (! $this->maskRepository->isFieldMaskedPartial('respondent_name')) {
            $model->set('respondent_name', 'label', $this->_('Name'));
        }
        $model->set('gr2t_start_date', 'label', $this->_('Start date'));
        $model->set('gr2t_end_date',   'label', $this->_('End date'));

        $filter = $this->getSearchFilter($action !== 'export');
        if (! (isset($filter['gr2t_id_organization']) && $filter['gr2t_id_organization'])) {
            $model->addFilter(array('gr2t_id_organization' => $this->currentUser->getRespondentOrgFilter()));
        }
        if (! (isset($filter['gr2t_id_track']) && $filter['gr2t_id_track'])) {
            $this->autofilterParameters['extraFilter'][1] = 0;
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
            return $model;
        }

        // Add the period filter - if any
        if ($where = $this->periodSelectRepository->createPeriodFilter($filter)) {
            $model->addFilter(array($where));
        }

        $fields['filler'] = new Expression('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)');

        $select = $this->resultFetcher->getSelect();
        $select->from('gems__rounds')
            ->columns(['gro_id_round', 'gro_id_order', 'gro_round_description', 'gro_icon_file'] + $fields)
            ->join('gems__surveys', 'gro_id_survey = gsu_id_survey', array('gsu_survey_name'))
            ->join('gems__track_fields', new PredicateExpression('gro_id_relationfield = gtf_id_field AND gtf_field_type = "relation"'), [], Select::JOIN_LEFT)
            ->join('gems__groups', 'gsu_id_primary_group =  ggp_id_group', [])
            ->where([
                'gro_id_track' => $filter['gr2t_id_track'],
                'gsu_active' => 1, //Only active surveys
            ])   
            ->order('gro_id_order');

        if (array_key_exists('fillerfilter', $filter)) {
            $having = new Having();
            $having->equalTo('filler', $filter['fillerfilter']);
            /** @phpstan-ignore-next-line https://github.com/laminas/laminas-db/issues/296 */
            $select->having($having);
        }
        $data = $this->resultFetcher->fetchAll($select);
        
        if (! $data) {
            return $model;
        }

        $status = $this->tokenRepository->getStatusExpression();
        $status = new Expression($status->getExpression());

        $select = $this->resultFetcher->getSelect();
        $select->from('gems__tokens')
            ->columns(['gto_id_respondent_track', 'gto_id_round', 'gto_id_token', 'status' => $status, 'gto_result'])
            ->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', [])
            ->where(['gto_id_track' => $filter['gr2t_id_track']])
            ->order(['grc_success', 'gto_id_respondent_track', 'gto_round_order']);

        // \MUtil\EchoOut\EchoOut::track($this->db->fetchAll($select));
        $newModel = $this->metaModelLoader->createModel(LaminasSelectModel::class, 'tok', $select);
        $metaModel = $newModel->getMetaModel();
        $metaModel->setKeys(['gto_id_respondent_track']);

        $transformer = $this->metaModelLoader->createTransformer(CrossTabTransformer::class);
        $transformer->addCrosstabField('gto_id_round', 'status', 'stat_')
                ->addCrosstabField('gto_id_round', 'gto_id_token', 'tok_')
                ->addCrosstabField('gto_id_round', 'gto_result', 'res_');

        foreach ($data as $row) {
            $name = 'stat_' . $row['gro_id_round'];
            $transformer->set($name, 'label', substr($row['gsu_survey_name'], 0, 2),
                    'description', sprintf("%s\n[%s]", $row['gsu_survey_name'], $row['gro_round_description']),
                    'noSort', true,
                    'round', $row['gro_round_description'],
                    'roundIcon', $row['gro_icon_file'],
                    'survey', $row['gsu_survey_name']
                    );
            $transformer->set('tok_' . $row['gro_id_round']);
            $transformer->set('res_' . $row['gro_id_round']);
        }

        $metaModel->addTransformer($transformer);
        // \MUtil\EchoOut\EchoOut::track($data);

        $joinTrans = $this->metaModelLoader->createTransformer(JoinTransformer::class);
        $joinTrans->addModel($newModel, array('gr2t_id_respondent_track' => 'gto_id_respondent_track'));

        $model->resetOrder();
        $model->set('gr2o_patient_nr');
        $model->set('respondent_name');
        $model->set('gr2t_start_date');
        $model->addTransformer($joinTrans);

        // Add masking if needed
        $model->applyMask();

        return $model;
    }

    /**
     * Get the model for export and have the option to change it before using for export
     * @return
     */
    public function getExportModel(): DataReaderInterface
    {
        $model         = parent::getExportModel();
        $statusColumns = $model->getColNames('label');
        $everyStatus   = $this->tokenRepository->getEveryStatus();
        foreach ($statusColumns as $colName) {
            // For the compliance columns, we add the translation for the letter codes and move the decription to the label
            // This way the column shows the full survey name and round description
            if (substr($colName, 0, 5) == 'stat_') {
                $model->set($colName, 'multiOptions', $everyStatus, 'label', $model->get($colName, 'description'));
            }
        }
        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Compliance');
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
        if (! isset($this->defaultSearchData['gr2t_id_organization'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $this->defaultSearchData['gr2t_id_organization'] = array_keys($orgs);
        }

        if (!isset($this->defaultSearchData['gr2t_id_track'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $tracks = $this->trackDataRepository->getTracksForOrgs($orgs);
            if (\count($tracks) == 1) {
                $this->defaultSearchData['gr2t_id_track'] = key($tracks);
            }
        }

        return parent::getSearchDefaults();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('track', 'tracks', $count);
    }
}
