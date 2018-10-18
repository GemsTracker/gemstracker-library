<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Action for consent overview
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class Gems_Default_TrackOverviewAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker_Overview_TableSnippet';

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
    protected $showParameters = array(
        'browse'        => true,
        'onEmpty'       => 'getOnEmptyText',
        'showMenu'      => true,
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        );

    /**
     * @var \Gems_Util
     */
    public $util;

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
    protected function createModel($detailed, $action)
    {
        $fields = array();
        // Export all
        if ('export' === $action) {
            $detailed = true;
        }

        $organizations = $this->util->getDbLookup()->getOrganizations();


        $fields[] = 'gtr_track_name';

        $sql      = "CASE WHEN gtr_organizations LIKE '%%|%s|%%' THEN 1 ELSE 0 END";

        foreach ($organizations as $orgId => $orgName) {
            $fields['O'.$orgId] = new \Zend_Db_Expr(sprintf($sql, $orgId));
        }

        $fields['total'] = new \Zend_Db_Expr("(LENGTH(gtr_organizations) - LENGTH(REPLACE(gtr_organizations, '|', ''))-1)");

        $fields[] = 'gtr_id_track';

        $select = $this->db->select();
        $select->from('gems__tracks', $fields);

        $model = new \MUtil_Model_SelectModel($select, 'track-verview');
        $model->setKeys(array('gtr_id_track'));
        $model->resetOrder();

        $model->set('gtr_track_name', 'label', $this->_('Track name'));

        $model->set('total', 'label', $this->_('Total'));
        $model->setOnTextFilter('total', array($this, 'noTextFilter'));

        foreach ($organizations as $orgId => $orgName) {
            $model->set('O' . $orgId, 'label', $orgName,
                    'tdClass', 'rightAlign',
                    'thClass', 'rightAlign');

            $model->setOnTextFilter('O' . $orgId, array($this, 'noTextFilter'));

            if ($action !== 'export') {
                $model->set('O'. $orgId, 'formatFunction', array($this, 'formatCheckmark'));
            }
        }

         // \MUtil_Model::$verbose = true;

        return $model;
    }

    public function formatCheckmark($value) {
        if ($value === 1) {
            return \MUtil_Html::create('span', array('class'=>'checked'))->append('V');
        }
        return;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->_('track per organization');
    }

    /**
     * Calculated fields can not exists in a where clause.
     *
     * We don't need to search on them with the text filter so we return
     * an empty array to disable text search.
     *
     * @param type $filter
     * @param type $name
     * @param type $field
     * @param type $model
     * @return type
     */
    public function noTextFilter($filter, $name, $field, $model)
    {
        return array();
    }
}