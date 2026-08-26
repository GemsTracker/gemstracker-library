<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Db\ResultFetcher;
use Gems\Exception\Coding;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Sql\Laminas\LaminasSelectModel;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Provides a visual overview of the track definition
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class TrackVisualDefinitionSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = [
        'filler'          => SORT_ASC,
        'gsu_survey_name' => SORT_ASC,
        'round_order'     => SORT_ASC
        ];
    
    protected $_model;

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_ROWS;
    
    protected $class = 'browser table visualtrack';
    
    protected bool $showMenu = false;
    
    /**
     * Id of the track to show
     * 
     * @var int 
     */
    public $trackId;
    
    public $trackUsage = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected readonly MetaModelLoader $modelLoader,
        protected readonly ResultFetcher $resultFetcher,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
        if (empty($this->trackId)) {
            throw new Coding('Provide a trackId to this snippet!');
        }
    }

    protected function createModel(): DataReaderInterface
    {
        if (! $this->_model instanceof LaminasSelectModel) {
            $select = $this->resultFetcher->getSelect();
            $select->from('gems__rounds')
                ->columns([new Expression('COALESCE(gro_round_description, "_null_")'), 'gro_round_description'])
                ->where(['gro_id_track' => $this->trackId]);
            $rounds = $this->resultFetcher->fetchPairs($select);

            $platform = $this->resultFetcher->getPlatform();

            $fields = [
                'gsu_survey_name' => new Expression('gems__surveys.gsu_survey_name'),
                'round_order' => new Expression('min(gro_id_order)')
            ];

            foreach ($rounds as $roundId => $round) {
                if ($round === null) {
                    $fields[$roundId] = new Expression('max(case when (gro_round_description IS NULL AND gro_condition > 0) then concat("C ", gcon_name) when gro_round_description IS NULL then "X" else NULL end)');
                    continue;
                }
                $fields[$roundId] = new Expression('max(case when (gro_round_description = ' . $platform->quoteValue($round) . ' AND gro_condition > 0) then concat("C ", gcon_name) when gro_round_description = ' . $platform->quoteValue($round) . ' then "X" else NULL end)');
            }
            $fields['filler'] = new Expression('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)');

            $sql = $this->resultFetcher->getSelect();
            $sql->from('gems__rounds')
                ->join('gems__surveys', 'gro_id_survey = gsu_id_survey', [])
                ->join('gems__track_fields', new \Laminas\Db\Sql\Predicate\Expression('gro_id_relationfield = gtf_id_field AND gtf_field_type = "relation"'), [], Select::JOIN_LEFT)
                ->join('gems__groups', 'gsu_id_primary_group =  ggp_id_group', [], Select::JOIN_LEFT)
                ->join('gems__conditions', 'gro_condition =  gcon_id', [], Select::JOIN_LEFT)
                ->where([
                    'gro_active' => 1,
                    'gro_id_track' => $this->trackId
                ])->group(['gems__surveys.gsu_survey_name', $fields['filler']])
                ->columns($fields);


            $model = $this->modelLoader->createModel(LaminasSelectModel::class, 'track-plan', $sql);
            $metaModel = $model->getMetaModel();
            $metaModel->setKeys(['gsu_survey_name']);
            $metaModel->resetOrder();
            $metaModel->set('filler', ['label' => $this->_('Filler')]);
            $metaModel->set('gsu_survey_name', ['label' => $this->_('Survey')]);
            foreach ($rounds as $roundId => $round) {
                $metaModel->set($roundId, [
                    'label' => $round ?? ' ',
                    'formatFunction' => [$this, 'visualRoundStatus'],
                ]);
            }
            $this->_model = $model;
        }

        return $this->_model;
    }

    /**
     * Show a check or cross for true or false values
     *
     * @param string $value
     * @return mixed
     */
    public function visualRoundStatus($value)
    {
        $char = $value ? substr($value, 0, 1) : '';
        switch ($char) {
            case 'X':
                // yes

                return Html::create()->i(['class' => 'fa fa-check', 'style' => 'color: green;', 'title' => $this->_('Yes')]);
                break;
            case 'C':
                // Condition
                if (strlen($value) > 2) {
                    return Html::create()->i(['class' => 'fa fa-question-circle', 'style' => 'color: orange;', 'title' => sprintf($this->_('Condition: %s'), substr($value, 2))]);
                }
                return Html::create()->i(['class' => 'fa fa-question-circle', 'style' => 'color: orange;', 'title' => $this->_('Condition')]);
                break;
            default:
                return null;
        }
    }

}