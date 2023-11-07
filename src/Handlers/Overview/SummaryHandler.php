<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Overview;

use Gems\Legacy\CurrentUserRepository;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentSiblingsButtonRowSnippet;
use Gems\Snippets\Tracker\Summary\SummarySearchFormSnippet;
use Gems\Snippets\Tracker\Summary\SummaryTableSnippet;
use Gems\User\User;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Having;
use Laminas\Db\Sql\Predicate\Expression as PredicateExpression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Sql\Laminas\LaminasSelectModel;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class SummaryHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
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
    protected array $autofilterParameters = array(
        'browse'    => false,
        'extraSort' => ['gro_id_order' => SORT_ASC],
    );

    /**
     * The snippets used for the autofilter action.
     *
     * @var array String or array of snippets name
     */
    protected array $autofilterSnippets = [ 
        SummaryTableSnippet::class,
    ];

    /**
     *
     * @var \Gems\User\User
     */
    public User $currentUser;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var array String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        SummarySearchFormSnippet::class,
    ];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var array String or array of snippets name
     */
    protected array $indexStopSnippets = [
        CurrentSiblingsButtonRowSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder, 
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected Adapter $laminasDb,
        protected MetaModelLoader $metaModelLoader,
        protected PeriodSelectRepository $periodSelectRepository,
        protected TrackDataRepository $trackDataRepository,
    )
    {
        parent::__construct($responder, $translate);
        
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
        $select = $this->getSelect();
        
        $dataModel = $this->metaModelLoader->createModel(LaminasSelectModel::class, 'summary', $select);
        $metaModel = $dataModel->getMetaModel();

        // Make sure of filter and sort for these fields
        $metaModel->set('gro_id_order');
        $metaModel->set('gto_id_track');
        $metaModel->set('gto_id_organization');

        $metaModel->resetOrder();
        $metaModel->set('gro_round_description', 'label', $this->_('Round'));
        $metaModel->set('gsu_survey_name',       'label', $this->_('Survey'));
        $metaModel->set('answered', 'label', $this->_('Answered'));
        $metaModel->set('missed',   'label', $this->_('Missed'));
        $metaModel->set('open',     'label', $this->_('Open'));
        $metaModel->set('total',    'label', $this->_('Total'));
        // $metaModel->set('future',   'label', $this->_('Future'));
        // $metaModel->set('unknown',  'label', $this->_('Unknown'));
        // $metaModel->set('is',       'label', ' ');
        // $metaModel->set('success',  'label', $this->_('Success'));
        // $metaModel->set('removed',  'label', $this->_('Removed'));

        $metaModel->setMulti(['answered', 'missed', 'open', 'total'],
                'tdClass', 'centerAlign', 'thClass', 'centerAlign');

        $metaModel->set('filler',  'label', $this->_('Filler'), 'no_text_search', true);

        $filter = $this->getSearchFilter($action !== 'export');
        if (! (isset($filter['gto_id_organization']) && $filter['gto_id_organization'])) {
            $this->autofilterParameters['extraFilter']['gto_id_organization'] = $this->currentUser->getRespondentOrgFilter();
        }

        if (isset($filter['gto_id_track']) && $filter['gto_id_track']) {
            // Add the period filter
            if ($where = $this->periodSelectRepository->createPeriodFilter($filter)) {
                $select->join('gems__respondent2track', 'gto_id_respondent_track = gr2t_id_respondent_track', [], Select::JOIN_LEFT);
                $this->autofilterParameters['extraFilter'][] = $where;
            }
        } else {
            $this->autofilterParameters['extraFilter'][1] = 0;
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
        }

        return $dataModel;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Summary');
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
        if (! isset($this->defaultSearchData['gto_id_organization'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $this->defaultSearchData['gto_id_organization'] = array_keys($orgs);
        }

        if (!isset($this->defaultSearchData['gto_id_track'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $tracks = $this->trackDataRepository->getTracksForOrgs($orgs);
            if (\count($tracks) == 1) {
                $this->defaultSearchData['gto_id_track'] = key($tracks);
            }
        }

        return parent::getSearchDefaults();
    }
    
    public function getSelect() : Select
    {
        $fields['answered'] = new Expression("SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NOT NULL
            THEN 1 ELSE 0 END
            )");
        $fields['missed']   = new Expression('SUM(
            CASE
            WHEN grc_success = 1 AND
                 gto_completion_time IS NULL AND
                 gto_valid_until < CURRENT_TIMESTAMP AND
                 (gto_valid_from IS NOT NULL AND gto_valid_from <= CURRENT_TIMESTAMP)
            THEN 1 ELSE 0 END
            )');
        $fields['open']   = new Expression('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND
                gto_valid_from <= CURRENT_TIMESTAMP AND
                (gto_valid_until >= CURRENT_TIMESTAMP OR gto_valid_until IS NULL)
            THEN 1 ELSE 0 END
            )');
        $fields['total'] = new Expression('SUM(
            CASE
            WHEN grc_success = 1 AND (
                    gto_completion_time IS NOT NULL OR
                    (gto_valid_from IS NOT NULL AND gto_valid_from <= CURRENT_TIMESTAMP)
                )
            THEN 1 ELSE 0 END
            )');
        /*
        $fields['future'] = new Expression('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND gto_valid_from > CURRENT_TIMESTAMP
            THEN 1 ELSE 0 END
            )');
        $fields['unknown'] = new Expression('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND gto_valid_from IS NULL
            THEN 1 ELSE 0 END
            )');
        $fields['is']      = new Expression("'='");
        $fields['success'] = new Expression('SUM(
            CASE
            WHEN grc_success = 1
            THEN 1 ELSE 0 END
            )');
        $fields['removed'] = new Expression('SUM(
            CASE
            WHEN grc_success = 0
            THEN 1 ELSE 0 END
            )');
        // */

        $fields['filler'] = new Expression('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)');

        $sql = new Sql($this->laminasDb);
        $select = $sql->select('gems__tokens');
        $select->columns($fields)
               ->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', [])
               ->join('gems__rounds', 'gto_id_round = gro_id_round', ['gro_round_description', 'gro_id_survey'])
               ->join('gems__surveys', 'gro_id_survey = gsu_id_survey', ['gsu_survey_name'])
               ->join('gems__groups', 'gsu_id_primary_group =  ggp_id_group', [])
               ->join('gems__track_fields', new PredicateExpression('gto_id_relationfield = gtf_id_field AND gtf_field_type = "relation"'), [], Select::JOIN_LEFT)
               ->group(['gro_id_order', 'gro_round_description', 'gro_id_survey', 'gsu_survey_name', $fields['filler']]);

        $filter = $this->getSearchFilter();
        if (array_key_exists('fillerfilter', $filter)) {
            $having = new Having();
            $having->equalTo('filler', $filter['fillerfilter']);
            $select->having($having);
        }

        return $select;
    }
        
    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('track', 'tracks', $count);
    }
}