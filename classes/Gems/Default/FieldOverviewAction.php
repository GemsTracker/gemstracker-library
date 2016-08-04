<?php


/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.0 9-mrt-2015 17:15:29
 */
class Gems_Default_FieldOverviewAction extends \Gems_Controller_ModelSnippetActionAbstract
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
    protected $autofilterParameters = array('menuShowActions' => array('track' => 'show-track'));

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
    protected $indexStartSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Tracker_Compliance_ComplianceSearchFormSnippet'
        );

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
        //
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

        $trackId = $filter['gr2t_id_track'];
        $engine = $this->loader->getTracker()->getTrackEngine($trackId);
        $engine->addFieldsToModel($model, false);

        return $model;
    }

    /**
     * Outputs the model to excel, applying all filters and searches needed
     *
     * When you want to change the output, there are two places to check:
     *
     * 1. $this->addExcelColumns($model), where the model can be changed to have labels for columns you
     * need exported
     *
     * 2. $this->getExcelData($data, $model) where the supplied data and model are merged to get output
     * (by default all fields from the model that have a label)
     */
    public function excelAction()
    {
        $model = $this->getModel();
        $model->set('gr2t_id_respondent_track', 'label', 'attribute_4', 'order', '5');

        foreach ($model->getColNames('has_orig') as $name) {
            $model->set($name . '_orig', 'label', $model->get($name, 'label') . ' {RAW]');
        }

        return parent::excelAction();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Respondent Track fields');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track field', 'track fields', $count);
    }
}
