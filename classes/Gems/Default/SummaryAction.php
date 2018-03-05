<?php

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
class Gems_Default_SummaryAction extends \Gems_Controller_ModelSnippetActionAbstract
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
    protected $autofilterParameters = array(
        'browse'    => false,
        'extraSort' => array('gro_id_order' => SORT_ASC),
    );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker_Summary_SummaryTableSnippet';

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Tracker_Summary_SummarySearchFormSnippet');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $select = $this->getSelect();

        // \MUtil_Model::$verbose = true;
        $model = new \MUtil_Model_SelectModel($select, 'summary');

        // Make sure of filter and sort for these fields
        $model->set('gro_id_order');
        $model->set('gto_id_track');
        $model->set('gto_id_organization');

        $model->resetOrder();
        $model->set('gro_round_description', 'label', $this->_('Round'));
        $model->set('gsu_survey_name',       'label', $this->_('Survey'));
        $model->set('answered', 'label', $this->_('Answered'), 'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        $model->set('missed',   'label', $this->_('Missed'),   'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        $model->set('open',     'label', $this->_('Open'),     'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        $model->set('total',    'label', $this->_('Total'),    'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('future',   'label', $this->_('Future'),   'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('unknown',  'label', $this->_('Unknown'),  'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('is',       'label', ' ',                  'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('success',  'label', $this->_('Success'),    'tdClass', 'centerAlign', 'thClass', 'centerAlign');
        // $model->set('removed',  'label', $this->_('Removed'),  'tdClass', 'deleted centerAlign',
        //         'thClass', 'centerAlign');

        $model->set('filler',  'label', $this->_('Filler'));

        $filter = $this->getSearchFilter($action !== 'export');
        if (! (isset($filter['gto_id_organization']) && $filter['gto_id_organization'])) {
            $model->addFilter(array('gto_id_organization' => $this->currentUser->getRespondentOrgFilter()));
        }

        if (isset($filter['gto_id_track']) && $filter['gto_id_track']) {
            // Add the period filter
            if ($where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db)) {
                $select->joinInner('gems__respondent2track', 'gto_id_respondent_track = gr2t_id_respondent_track', array());
                $model->addFilter(array($where));
            }
        } else {
            $model->setFilter(array('1=0'));
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
        }

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
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
    public function getSearchDefaults()
    {
        if (! isset($this->defaultSearchData['gto_id_organization'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $this->defaultSearchData['gto_id_organization'] = array_keys($orgs);
        }

        return parent::getSearchDefaults();
    }

   /**
     * Select creation function, allowes overruling in child classes
     *
     * @return \Zend_Db_Select
     */
    public function getSelect()
    {
        $select = $this->db->select();

        $fields['answered'] = new \Zend_Db_Expr("SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NOT NULL
            THEN 1 ELSE 0 END
            )");
        $fields['missed']   = new \Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND 
                 gto_completion_time IS NULL AND 
                 gto_valid_until < CURRENT_TIMESTAMP AND
                 (gto_valid_from IS NOT NULL AND gto_valid_from <= CURRENT_TIMESTAMP)
            THEN 1 ELSE 0 END
            )');
        $fields['open']   = new \Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND
                gto_valid_from <= CURRENT_TIMESTAMP AND
                (gto_valid_until >= CURRENT_TIMESTAMP OR gto_valid_until IS NULL)
            THEN 1 ELSE 0 END
            )');
        $fields['total'] = new \Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND (
                    gto_completion_time IS NOT NULL OR
                    (gto_valid_from IS NOT NULL AND gto_valid_from <= CURRENT_TIMESTAMP)
                )
            THEN 1 ELSE 0 END
            )');
        /*
        $fields['future'] = new \Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND gto_valid_from > CURRENT_TIMESTAMP
            THEN 1 ELSE 0 END
            )');
        $fields['unknown'] = new \Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1 AND gto_completion_time IS NULL AND gto_valid_from IS NULL
            THEN 1 ELSE 0 END
            )');
        $fields['is']      = new \Zend_Db_Expr("'='");
        $fields['success'] = new \Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 1
            THEN 1 ELSE 0 END
            )');
        $fields['removed'] = new \Zend_Db_Expr('SUM(
            CASE
            WHEN grc_success = 0
            THEN 1 ELSE 0 END
            )');
        // */

        $fields['filler'] = new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)');

        $select = $this->db->select();
        $select->from('gems__tokens', $fields)
                ->joinInner('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array())
                ->joinInner('gems__rounds', 'gto_id_round = gro_id_round',
                        array('gro_round_description', 'gro_id_survey'))
                ->joinInner('gems__surveys', 'gro_id_survey = gsu_id_survey',
                        array('gsu_survey_name'))
                ->joinInner('gems__groups', 'gsu_id_primary_group =  ggp_id_group', array())
                ->joinLeft('gems__track_fields', 'gto_id_relationfield = gtf_id_field AND gtf_field_type = "relation"', array())
                ->group(array('gro_id_order', 'gro_round_description', 'gsu_survey_name', 'filler'));
        
        $filter = $this->getSearchFilter();
        if (array_key_exists('fillerfilter', $filter)) {
            $select->having('filler = ?', $filter['fillerfilter']);
        }

        return $select;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
    }
}