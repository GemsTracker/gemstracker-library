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
use Gems\MenuNew\RouteHelper;
use MUtil\Model\SelectModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetOptions;
use Zend_Db_Adapter_Abstract;

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
        RouteHelper $routeHelper,
        TranslatorInterface $translate,
        protected Zend_Db_Adapter_Abstract $db,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $routeHelper, $translate);
        if (empty($this->trackId)) {
            throw new Coding('Provide a trackId to this snippet!');
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (!$this->_model instanceof \MUtil\Model\SelectModel) {

            $select = $this->db->select()->distinct()->from('gems__rounds', ['gro_round_description', 'gro_round_description'])->where('gro_id_track = ?', $this->trackId);
            $rounds = $this->db->fetchPairs($select);

            $fields = [
                'gems__surveys.gsu_survey_name',
                'round_order' => new \Zend_Db_Expr('min(gro_id_order)')
            ];
            foreach ($rounds as $round) {
                if ($round === null) {
                    continue;
                }
                $fields[$round] = new \Zend_Db_Expr('max(case when (gro_round_description = ' . $this->db->quote($round) . ' AND gro_condition > 0) then "C" when gro_round_description = ' . $db->quote($round) . ' then "X" else NULL end)');
            }
            $fields['filler'] = new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)');

            $sql = $this->db->select()->from('gems__rounds', [])
                    ->join('gems__surveys', 'gro_id_survey = gsu_id_survey', [])
                    ->joinLeft('gems__track_fields', 'gro_id_relationfield = gtf_id_field AND gtf_field_type = "relation"', array())
                    ->joinLeft('gems__groups', 'gsu_id_primary_group =  ggp_id_group', array())
                    ->where('gro_active = 1')   //Only active rounds
                    ->where('gro_id_track = ?', $this->trackId)
                    ->group(['gro_id_survey', 'filler'])
                    ->columns($fields);

            $model = new SelectModel($sql, 'track-plan');
            //$model->setKeys(array('gsu_survey_name'));
            $model->resetOrder();
            $model->set('filler', 'label', $this->_('Filler'));
            $model->set('gsu_survey_name', 'label', $this->_('Survey'));            
            foreach ($rounds as $round) {
                $model->set($round, 'label', $round, 'formatFunction', [$this, 'visualRoundStatus']);
            }
            $this->_model = $model;
        }

        return $this->_model;
    }

    /**
     * Show a check or cross for true or false values
     *
     * @param bool $value
     * @return mixed
     */
    public function visualRoundStatus($value)
    {
        switch ($value) {
            case 'X':
                // yes

                return Html::create()->i(['class' => 'fa fa-check', 'style' => 'color: green;', 'title' => $this->_('Yes')]);
                break;
            case 'C':
                // Condition
                return Html::create()->i(['class' => 'fa fa-question-circle', 'style' => 'color: orange;', 'title' => $this->_('Condition')]);
                break;
            default:
                return null;
        }
    }

}