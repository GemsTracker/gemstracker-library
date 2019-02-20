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

/**
 * Provides a visual overview of the track definition
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class TrackVisualDefinitionSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('round_order' => SORT_ASC);
    
    protected $_model;

    /**
     * One of the \MUtil_Model_Bridge_BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_ROWS;
    
    protected $class = 'browser table visualtrack';
    
    protected $db;
    
    protected $showMenu = false;
    
    /**
     * Id of the track to show
     * 
     * @var int 
     */
    public $trackId;
    
    public $trackUsage = false;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        if (empty($this->trackId)) {
            throw new Gems_Exception_Coding('Provide a trackId to this snippet!');
        }

        $model = $this->getModel();
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (!$this->_model instanceof \MUtil_Model_SelectModel) {
            $trackId = $this->trackId;

            $db     = $this->db;
            $select = $db->select()->distinct()->from('gems__rounds', ['gro_round_description', 'gro_round_description'])->where('gro_id_track = ?', $trackId);
            $rounds = $this->db->fetchPairs($select);

            $fields = [
                'gems__surveys.gsu_survey_name',
                'round_order' => new \Zend_Db_Expr('min(gro_id_order)')
            ];
            foreach ($rounds as $round) {
                $fields[$round] = new \Zend_Db_Expr('max(case when (gro_round_description = ' . $db->quote($round) . ' AND gro_condition IS NULL) then "X" when gro_round_description = ' . $db->quote($round) . ' then "C" else NULL end)');
            }
            $fields['filler'] = new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)');

            $sql = $this->db->select()->from('gems__rounds', [])
                    ->join('gems__surveys', 'gro_id_survey = gsu_id_survey', [])
                    ->joinLeft('gems__track_fields', 'gro_id_relationfield = gtf_id_field AND gtf_field_type = "relation"', array())
                    ->joinLeft('gems__groups', 'gsu_id_primary_group =  ggp_id_group', array())
                    ->where('gro_active = 1')   //Only active rounds
                    ->where('gro_id_track = ?', $trackId)
                    ->group(['gro_id_survey', 'filler'])
                    ->columns($fields);

            $model = new \MUtil_Model_SelectModel($sql, 'track-plan');
            //$model->setKeys(array('gsu_survey_name'));
            $model->resetOrder();
            $model->set('gsu_survey_name', 'label', $this->_('Survey'));
            $model->set('filler', 'label', $this->_('Filler'));
            foreach ($rounds as $round) {
                $model->set($round, 'label', $round);
            }
            $this->_model = $model;
        }

        return $this->_model;
    }
}