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
class Gems_Default_ComplianceAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker_Compliance_ComplianceTableSnippet';

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Tracker_Compliance_ComplianceSearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Tracker_TokenStatusLegenda', 'Generic\\CurrentButtonRowSnippet');

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
        $model = new \Gems_Model_JoinModel('resptrack' , 'gems__respondent2track');
        $model->addTable('gems__respondent2org', array(
            'gr2t_id_user' => 'gr2o_id_user',
            'gr2t_id_organization' => 'gr2o_id_organization'
            ));
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__reception_codes', array('gr2t_reception_code' => 'grc_id_reception_code'));
        $model->addFilter(array('grc_success' => 1));

        $model->resetOrder();
        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));
        $model->set('gr2t_start_date', 'label', $this->_('Start date'), 'dateFormat', 'dd-MM-yyyy');
        $model->set('gr2t_end_date',   'label', $this->_('End date'), 'dateFormat', 'dd-MM-yyyy');

        $filter = $this->getSearchFilter($action !== 'export');
        if (! (isset($filter['gr2t_id_organization']) && $filter['gr2t_id_organization'])) {
            $model->addFilter(array('gr2t_id_organization' => $this->currentUser->getRespondentOrgFilter()));
        }
        if (! (isset($filter['gr2t_id_track']) && $filter['gr2t_id_track'])) {
            $model->setFilter(array('1=0'));
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
            return $model;
        }

        // Add the period filter - if any
        if ($where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db)) {
            $model->addFilter(array($where));
        }

        $select = $this->db->select();
        $select->from('gems__rounds', array('gro_id_round', 'gro_id_order', 'gro_round_description', 'gro_icon_file'))
                ->joinInner('gems__surveys', 'gro_id_survey = gsu_id_survey', array('gsu_survey_name'))
                ->where('gro_id_track = ?', $filter['gr2t_id_track'])
                ->where('gsu_active = 1')   //Only active surveys
                ->order('gro_id_order');

        if (isset($filter['gsu_id_primary_group']) && $filter['gsu_id_primary_group']) {
            $select->where('gsu_id_primary_group = ?', $filter['gsu_id_primary_group']);
        }
        $data = $this->db->fetchAll($select);

        if (! $data) {
            return $model;
        }

        $status = $this->util->getTokenData()->getStatusExpression();

        $select = $this->db->select();
        $select->from('gems__tokens', array(
            'gto_id_respondent_track', 'gto_id_round', 'gto_id_token', 'status' => $status, 'gto_result',
            ))->joinInner('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array())
                // ->where('grc_success = 1')
                ->where('gto_id_track = ?', $filter['gr2t_id_track'])
                ->order('grc_success')
                ->order('gto_id_respondent_track')
                ->order('gto_round_order');

        // \MUtil_Echo::track($this->db->fetchAll($select));
        $newModel = new \MUtil_Model_SelectModel($select, 'tok');
        $newModel->setKeys(array('gto_id_respondent_track'));

        $transformer = new \MUtil_Model_Transform_CrossTabTransformer();
        $transformer->addCrosstabField('gto_id_round', 'status', 'stat_')
                ->addCrosstabField('gto_id_round', 'gto_id_token', 'tok_')
                ->addCrosstabField('gto_id_round', 'gto_result', 'res_');

        foreach ($data as $row) {
            $name = 'stat_' . $row['gro_id_round'];
            $transformer->set($name, 'label', \MUtil_Lazy::call('substr', $row['gsu_survey_name'], 0, 2),
                    'description', sprintf("%s\n[%s]", $row['gsu_survey_name'], $row['gro_round_description']),
                    'noSort', true,
                    'round', $row['gro_round_description'],
                    'roundIcon', $row['gro_icon_file']
                    );
            $transformer->set('tok_' . $row['gro_id_round']);
            $transformer->set('res_' . $row['gro_id_round']);
        }

        $newModel->addTransformer($transformer);
        // \MUtil_Echo::track($data);

        $joinTrans = new \MUtil_Model_Transform_JoinTransformer();
        $joinTrans->addModel($newModel, array('gr2t_id_respondent_track' => 'gto_id_respondent_track'));

        $model->resetOrder();
        $model->set('gr2o_patient_nr');
        $model->set('gr2t_start_date');
        $model->addTransformer($joinTrans);

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
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
    public function getSearchDefaults()
    {
        if (! isset($this->defaultSearchData['gr2t_id_organization'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $this->defaultSearchData['gr2t_id_organization'] = array_keys($orgs);
        }

        return parent::getSearchDefaults();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }
}
