<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldReportAction.php 2534 2015-05-05 18:07:37Z matijsdejong $
 */

use Gems\Tracker\Model\FieldDataModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 30-nov-2014 17:50:22
 */
class Gems_Default_FieldReportAction extends \Gems_Controller_ModelSnippetActionAbstract
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
        'browse' => false,
        'columns' => 'getBrowseColumns',
        );

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
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $engine;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Tracker_Fields_FieldReportSearchSnippet');

    /**
     * Where statement filtering out organisations
     *
     * @var string
     */
    protected $orgWhere;

    /**
     * The number of instances of the currently selected track id
     *
     * @var int
     */
    protected $trackCount;

    /**
     * The number of instances of the current field that have been filled
     *
     * @var int
     */
    protected $trackFilled;

    /**
     * The currently selected track id
     *
     * @var int
     */
    protected $trackId;

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
        $filter = $this->getSearchFilter($action !== 'export');

        // Return empty model when no track sel;ected
        if (! (isset($filter['gtf_id_track']) && $filter['gtf_id_track'])) {
            $model = new \Gems_Model_JoinModel('trackfields' , 'gems__track_fields');
            $model->set('gtf_field_name', 'label', $this->_('Name'));
            $model->setFilter(array('1=0'));
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');

            return $model;
        }

        $this->trackId = $filter['gtf_id_track'];

        $tracker      = $this->loader->getTracker();
        $this->engine = $tracker->getTrackEngine($this->trackId);

        $orgs         = $this->currentUser->getRespondentOrgFilter();
        if (isset($filter['gr2t_id_organization'])) {
            $orgs = array_intersect($orgs, (array) $filter['gr2t_id_organization']);
        }
        $this->orgWhere = " AND gr2t_id_organization IN (" . implode(", ", $orgs) . ")";

        $sql     = "SELECT COUNT(*)
            FROM gems__respondent2track INNER JOIN gems__reception_codes ON gr2t_reception_code = grc_id_reception_code
            WHERE gr2t_id_track = ? AND grc_success = 1" . $this->orgWhere;

        $this->trackCount = $this->db->fetchOne($sql, $this->trackId);

        $model = $this->engine->getFieldsMaintenanceModel();
        //$model->setFilter($filter);

        // $model->addColumn(new \Zend_Db_Expr($trackCount), 'trackcount');
        // $model->addColumn(new \Zend_Db_Expr("(SELECT COUNT())"), 'fillcount');

        $model->set('trackcount', 'label', $this->_('Tracks'));
        $model->setOnLoad('trackcount', $this->trackCount);

        $model->set('fillcount', 'label', $this->_('Filled'));
        $model->setOnLoad('fillcount', array($this, 'fillCount'));

        $model->set('emptycount', 'label', $this->_('Empty'));
        $model->setOnLoad('emptycount', array($this, 'emptyCount'));

        $model->set('valuecount', 'label', $this->_('Unique values'));
        $model->setOnLoad('valuecount', array($this, 'valueCount'));

        return $model;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function emptyCount($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $value = $this->trackCount - $this->trackFilled;
        return sprintf(
                $this->_('%d (%d%%)'),
                $value,
                $this->trackCount ? round($value / $this->trackCount * 100, 0) : 0
            );
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function fillCount($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $model = $this->engine->getFieldsDataStorageModel();

        if (! $model instanceof FieldDataModel) {
            return null;
        }

        $subName  = $model->getModelNameForRow($context);
        $sql = sprintf("SELECT COUNT(*)
            FROM %s INNER JOIN gems__respondent2track ON %s = gr2t_id_respondent_track
                INNER JOIN gems__reception_codes ON gr2t_reception_code = grc_id_reception_code
            WHERE %s = %s AND %s IS NOT NULL AND gr2t_id_track = %d AND grc_success = 1" . $this->orgWhere,
                $model->getTableName($subName),
                $model->getFieldName('gr2t2f_id_respondent_track', $subName),
                $model->getFieldName('gr2t2f_id_field', $subName),
                $context['gtf_id_field'],
                $model->getFieldName('gr2t2f_value', $subName),
                $this->trackId
                );

        // \MUtil_Echo::track($sql);
        $this->trackFilled = $this->db->fetchOne($sql);

        $value = $this->trackFilled;
        return sprintf(
                $this->_('%d (%d%%)'),
                $value,
                $this->trackCount ? round($value / $this->trackCount * 100, 0) : 0
            );
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function valueCount($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $model = $this->engine->getFieldsDataStorageModel();

        if (! $model instanceof FieldDataModel) {
            return null;
        }

        $subName  = $model->getModelNameForRow($context);
        $sql = sprintf("SELECT COUNT(DISTINCT %s)
            FROM %s INNER JOIN gems__respondent2track ON %s = gr2t_id_respondent_track
                INNER JOIN gems__reception_codes ON gr2t_reception_code = grc_id_reception_code
            WHERE %s = %s AND %s IS NOT NULL AND gr2t_id_track = %d AND grc_success = 1" . $this->orgWhere,
                $model->getFieldName('gr2t2f_value', $subName),
                $model->getTableName($subName),
                $model->getFieldName('gr2t2f_id_respondent_track', $subName),
                $model->getFieldName('gr2t2f_id_field', $subName),
                $context['gtf_id_field'],
                $model->getFieldName('gr2t2f_value', $subName),
                $this->trackId
                );

        // \MUtil_Echo::track($sql);
        $value = $this->db->fetchOne($sql);
        return sprintf(
                $this->_('%d (uses per value: %01.2f)'),
                $value,
                $value ? $this->trackFilled / $value : 0
            );
    }

    /**
     * Get the browse columns
     * @return array
     */
    public function getBrowseColumns()
    {
        $filter = $this->getSearchFilter(true);
        if (! (isset($filter['gtf_id_track']) && $filter['gtf_id_track'])) {
            return array(
                array('gtf_field_name'),
                );
        }
        return array(
            array('gtf_field_name'),
            array('gtf_id_order'),
            array('gtf_field_type'),
            array('trackcount'),
            array('emptycount'),
            array('fillcount'),
            array('valuecount'),
        );
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Track fields');
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
