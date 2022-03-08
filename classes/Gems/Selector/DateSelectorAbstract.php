<?php

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class Gems_Selector_DateSelectorAbstract extends \MUtil_Translate_TranslateableAbstract
{
    const DATE_FACTOR = 'df';
    const DATE_GROUP  = 'dg';
    const DATE_TYPE   = 'dt';

    private $_actionKey;

    /**
     * @var array $_fields
     */
    private $_fields;

    private $_model;

    /**
     *
     * @var string
     */
    protected $dataCellClass = 'centerAlign timeResult';

    /**
     * The name of the database table to use as the main table.
     *
     * @var string
     */
    protected $dataTableName;

    /**
     * The date the current period ends
     *
     * @var \MUtil_Date
     */
    protected $dateCurrentEnd;

    /**
     * The date the current period starts
     *
     * @var \MUtil_Date
     */
    protected $dateCurrentStart;

    /**
     * The number of dateTypes in the future or (when negative) before now.
     *
     * @var integer
     */
    protected $dateFactor;

    /**
     * Stores the dateFactor's to use so that the current period will be roughly the same
     * as the current period.
     *
     * Assigned by createModel() SHOULD BECOME PRIVATE
     *
     * @var array
     */
    protected $dateFactorChanges;

    /**
     * The name of the field where the date is calculated from
     *
     * @var string
     */
    protected $dateFrom;

    /**
     * The group (row) of data selected.
     *
     * @var integer
     */
    protected $dateGroup;

    /**
     * The number of periods shown before and after the current period.
     *
     * @var integer
     */
    protected $dateRange = 3;

    /**
     * Character D W M Y for one of the date types.
     *
     * @var string
     */
    protected $dateType = 'W';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @param string $name
     * @return \Gems_Selector_SelectorField
     */
    protected function addField($name)
    {
        $field = new \Gems_Selector_SelectorField($name);

        $this->_fields[$name] = $field;

        return $field;
    }

    /**
     * Creates the base model.
     *
     * @return \MUtil_Model_SelectModel
     */
    protected function createModel()
    {
        $groupby['period_1'] = new \Zend_Db_Expr("YEAR($this->dateFrom)");

        $date = new \MUtil_Date();

        switch ($this->dateType) {
            case 'D':
                $keyCount = 1;
                $groupby['period_1'] = new \Zend_Db_Expr("CONVERT($this->dateFrom, DATE)");

                $date->setTime(0);
                $date->addDay($this->dateFactor - $this->dateRange);

                $start = $date->getIso();
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = clone $date;
                        $this->dateCurrentEnd->setTimeToDayEnd();
                    }

                    $values = array();
                    $values['period_1'] = $date->get('yyyy-MM-dd');
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date->addDay(1);
                }
                $date->subSecond(1);
                $end = $date->getIso();
                break;

            case 'W':
                $keyCount = 2;

                // Use MONDAY as start of week
                $groupby['period_1'] = new \Zend_Db_Expr("substr(YEARWEEK(gto_valid_from, 3),1,4)");
                //$groupby['period_1'] = new \Zend_Db_Expr("YEAR($this->dateFrom) - CASE WHEN WEEK($this->dateFrom, 1) = 0 THEN 1 ELSE 0 END");
                $groupby['period_2'] = new \Zend_Db_Expr("WEEK($this->dateFrom, 3)");

                $date->setWeekday(1);
                $date->setTime(0);
                $date->addWeek($this->dateFactor - $this->dateRange);

                $start = $date->getIso();
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = clone $date;
                        $this->dateCurrentEnd->addWeek(1)->subSecond(1);
                    }

                    $values = array();
                    $values['period_1'] = $date->get(\Zend_Date::YEAR);
                    $values['period_2'] = (int) $date->get(\Zend_Date::WEEK);   // Use constant but drop leading zero
                    // When monday is in the previous year, add one to the year
                    if ($date->get(\Zend_Date::DAY_OF_YEAR)>14 && $date->get(\Zend_Date::WEEK) == 1) {
                        $values['period_1'] =  $values['period_1'] + 1;
                    }
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date->addWeek(1);
                }
                $date->subSecond(1);
                $end = $date->getIso();
                break;

            case 'M':
                $keyCount = 2;
                $groupby['period_2'] = new \Zend_Db_Expr("MONTH($this->dateFrom)");

                $date->setDay(1);
                $date->setTime(0);
                $date->addMonth($this->dateFactor - $this->dateRange);

                $start = $date->getIso();
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = clone $date;
                        $this->dateCurrentEnd->addMonth(1)->subSecond(1);
                    }

                    $values = array();
                    $values['period_1'] = $date->get(\Zend_Date::YEAR);
                    $values['period_2'] = $date->get(\Zend_Date::MONTH);
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date->addMonth(1);
                }
                $date->subSecond(1);
                $end = $date->getIso();
                break;

            case 'Y':
                $keyCount = 1;
                $date->setDay(1);
                $date->setMonth(1);
                $date->setTime(0);
                $date->addYear($this->dateFactor - $this->dateRange);
                $start = $date->getIso();
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = clone $date;
                        $this->dateCurrentEnd->addYear(1)->subSecond(1);
                    }

                    $values = array();
                    $values['period_1'] = $date->get(\Zend_Date::YEAR);
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date->addYear(1);
                }
                $date->subSecond(1);
                $end = $date->getIso();
                break;

            default:
                throw new \Gems_Exception_Coding('Incorrect date_type value: ' . $this->dateType);
        }
        $where = "$this->dateFrom BETWEEN '$start' AND '$end'";

        for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
            $requiredRows[$i]['date_factor'] = $this->dateFactor + $i;
            $requiredRows[$i]['df_link']     = null;
            $requiredRows[$i]['df_label']    = null;
        }
        if ($this->dateRange > 0) {
            $requiredRows[-$this->dateRange]['df_link']  = $this->dateFactor - ($this->dateRange * 2);
            $requiredRows[-$this->dateRange]['df_label'] = $this->_('<<');
            $requiredRows[ $this->dateRange]['df_link']  = $this->dateFactor + ($this->dateRange * 2);
            $requiredRows[ $this->dateRange]['df_label'] = $this->_('>>');
            if ($this->dateRange > 1) {
                $i = intval($this->dateRange / 2);
                $requiredRows[-$i]['df_link']  = $this->dateFactor - 1;
                $requiredRows[-$i]['df_label'] = $this->_('<');
                $requiredRows[ $i]['df_link']  = $this->dateFactor + 1;
                $requiredRows[ $i]['df_label'] = $this->_('>');
            }
            $requiredRows[ 0]['df_link']  = $this->dateFactor ? '0' : null;
            $requiredRows[ 0]['df_label'] = $this->_('Now!');
        }

        if ($this->dateFactor) {
            $today = new \MUtil_Date();
            $this->dateFactorChanges['D'] = $this->dateCurrentStart->diffDays($today);
            $this->dateFactorChanges['W'] = $this->dateCurrentStart->diffWeeks($today);
            $this->dateFactorChanges['M'] = $this->dateCurrentStart->diffMonths($today);
            $this->dateFactorChanges['Y'] = $this->dateCurrentStart->diffYears($today);
        } else {
            $this->dateFactorChanges = array_fill_keys(array('D', 'W', 'M', 'Y'), 0);
        }
        // \MUtil_Echo::track($requiredRows);
        // \MUtil_Echo::rs($start, $end, $where);

        $select = new \Zend_Db_Select($this->db);
        $select->from($this->dataTableName, $groupby + $this->getDbFields());
        $select->where($where);
        $select->group($groupby);

        $this->processSelect($select);

        // \MUtil_Echo::r((string) $select);

        $model = new \MUtil_Model_SelectModel($select, $this->dataTableName);

        // Display by column cannot use formatFunction as it is a simple repeater
        // $model->set('duration_avg', 'formatFunction', $this->util->getLocalized()->formatNumber);

        $transformer = new \MUtil_Model_Transform_RequiredRowsTransformer();
        $transformer->setDefaultRow($this->getDefaultRow());
        $transformer->setRequiredRows($requiredRows);
        $transformer->setKeyItemCount($keyCount);
        $model->addTransformer($transformer);

        return $model;
    }

    protected function getDateDescriptions()
    {
        return array(
            'D' => $this->_('Show by day'),
            'W' => $this->_('Show by week'),
            'M' => $this->_('Show by month'),
            'Y' => $this->_('Show by year'),
            );
    }

    protected function getDateLabels()
    {
        return array_map('strtolower', array(
            'D' => $this->_('D'),
            'W' => $this->_('W'),
            'M' => $this->_('M'),
            'Y' => $this->_('Y'),
            ));
    }

    protected function getDbFields()
    {
        $results = array();
        foreach ($this->getFields() as $name => $field) {
            $results[$name] = $field->getSQL();
        }
        return $results;
    }

    /**
     * Returns defaults for all field values. Can be overruled.
     *
     * @return array An array with appropriate default values for use in \MUtil_Model_Transform_RequiredRowsTransformer
     */
    protected function getDefaultRow()
    {
        $results = array();
        foreach ($this->getFields() as $name => $field) {
            $results[$name] = $field->getDefault();
        }
        return $results;
    }

    /**
     * Returns defaults for this filter. Can be overruled.
     *
     * @return array An array with appropriate default values for filtering
     */
    public function getDefaultSearchData()
    {
        return array(
            self::DATE_FACTOR => 0,
            self::DATE_GROUP => null,
            self::DATE_TYPE => 'W');
    }

    protected function getFields()
    {
        if (! $this->_fields) {
            $this->loadFields();
        }

        return $this->_fields;
    }

    /**
     * Returns the base model.
     *
     * @return \MUtil_Model_Transform_RequiredRowsTransformer
     */
    public function getModel()
    {
        if (! $this->_model) {
            $this->_model = $this->createModel();
        }

        return $this->_model;
    }

    /**
     * Prcesses the filter for the date selector and return the filter to use instead
     *
     * @param string $dateField
     * @return array The new complete filter to use
     */
    public function getSelectorFilterPart($dateField = null)
    {
        // \MUtil_Echo::track($filter);
        $newfilter = [];

        if ($this->dateCurrentStart && $this->dateCurrentEnd) {
            if (null === $dateField) {
                $dateField = $this->dateFrom;
            }
            $start = $this->dateCurrentStart->getIso();
            $end   = $this->dateCurrentEnd->getIso();
            $newfilter[] = "$dateField BETWEEN '$start' AND '$end'";
        }

        if ($this->dateGroup) {
            $fields = $this->getFields();
            if (isset($fields[$this->dateGroup])) {
                if ($groupfilter = $fields[$this->dateGroup]->getFilter()) {
                    $newfilter[] = $groupfilter;
                }
            }
        }

        return $newfilter;
    }

    public function getTable($baseurl)
    {
        $model    = $this->getModel();
        $bridge   = $model->getBridgeFor('table', array('class' => 'timeTable table table-condensed table-bordered'));
        $repeater = $bridge->getRepeater();

        $bridge->setBaseUrl(array($this->_actionKey => 'index', 'reset' => null) + $baseurl); // + $model->getFilter();

        $columnClass = \MUtil_Lazy::iff($repeater->range, null, 'selectedColumn');

        $this->setTableHeader($bridge, $repeater, $columnClass);
        $this->setTableBody(  $bridge, $repeater, $columnClass);
        $this->setTableFooter($bridge, $repeater, $columnClass);

        return $bridge->getTable();
    }

    /**
     * Loads the fields for this instance.
     */
    abstract protected function loadFields();

    /**
     * Stub function to allow extension of standard one table select.
     *
     * @param \Zend_Db_Select $select
     */
    protected function processSelect(\Zend_Db_Select $select)
    {  }

    /**
     * Processing of filter, sets the selected position in the overview table.
     * Can be overriden.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param array $filter
     * @return array The filter minus the Selector fields
     */
    public function processSelectorFilter(\Zend_Controller_Request_Abstract $request, array $filter)
    {
        $this->_actionKey = $request->getActionKey();

        $defaults = $this->getDefaultSearchData();

        $this->dateFactor = $this->processSelectorFilterName(self::DATE_FACTOR, $request, $filter, $defaults);
        $this->dateGroup  = $this->processSelectorFilterName(self::DATE_GROUP, $request, $filter, $defaults);
        $this->dateType   = $this->processSelectorFilterName(self::DATE_TYPE, $request, $filter, $defaults);

        unset($filter[self::DATE_FACTOR], $filter[self::DATE_GROUP], $filter[self::DATE_TYPE]);

        $model = $this->getModel();

        return $filter;
    }

    protected function processSelectorFilterName($name, \Zend_Controller_Request_Abstract $request, array $filter, array $defaults = null)
    {
        if (isset($filter[$name])) {
            return $filter[$name];
        }
        if ($val = $request->getParam($name)) {
            return $val;
        }
        if (is_array($defaults)) {
            if (isset($defaults[$name])) {
                return $defaults[$name];
            }
        } else {
            return $defaults;
        }
    }
    
    /**
     * Set the filter for the whole table
     * @param array $filter
     */
    public function setFilter(array $filter)
    {
        $model = $this->getModel();
        $model->setFilter($filter);
    }

    protected function setTableBody(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Lazy_RepeatableInterface $repeater, $columnClass)
    {
        $baseurl = $bridge->getBaseUrl();
        $onEmpty = $this->_('-');

        foreach ($this->getFields() as $name => $field) {
            $bridge->tr(array('class' => ($this->dateGroup == $name) ? 'selectedRow' : null));

            // Left cell
            $td = $bridge->td($field->getLabel());
            $td->class = $field->getLabelClass();

            // Repeating column
            $href = $field->getHRef($repeater, $baseurl);
            $td = $bridge->td();
            $td->a($href, $repeater->$name);
            $td->class = array($this->dataCellClass, $field->getClass(), $columnClass);
            $td->onclick = array('location.href=\'', $href, '\';');
            $td->setOnEmpty($onEmpty);
            $td->setRepeater($repeater);
            $td->setRepeatTags(true);
        }
    }

    protected function setTableFooter(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Lazy_RepeatableInterface $repeater, $columnClass)
    {
        $baseurl = $bridge->getBaseUrl();

        // Empty cell for left column
        $bridge->tf();

        $href = array(
            self::DATE_FACTOR => $repeater->df_link,
            \MUtil_Model::AUTOSEARCH_RESET => null,
            ) + $baseurl;

        // Repeating column
        $tf = $bridge->tf();
        $tf->class = array($this->dataCellClass, $columnClass);
        $tf->iflink($repeater->df_link->strlen(),
            array('href' => $href, $repeater->df_label, 'class' => 'browselink btn btn-xs'),
            array($repeater->df_label, 'class' => 'browselink btn btn-xs disabled'));
        $tf->setRepeater($repeater);
        $tf->setRepeatTags(true);
    }

    protected function setTableHeader(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Lazy_RepeatableInterface $repeater, $columnClass)
    {
        $baseurl = $bridge->getBaseUrl();

        // Left cell with period types
        $th = $bridge->th($this->_('Period'), ' ');
        $th->class = 'middleAlign';
        $thdiv = $th->span()->spaced(); // array('class' => 'rightFloat'));
        $contents = $this->getDateLabels();
        foreach ($this->getDateDescriptions() as $letter => $title) {
            if (isset($contents[$letter])) {
                $content = $contents[$letter];
            } else {
                $content = strtolower($this->_($letter));
            }
            if ($letter == $this->dateType) {
                $thdiv->span($content, array('class' => 'browselink btn btn-primary btn-xs disabled'));
            } else {
                $thdiv->a(array(self::DATE_TYPE => $letter, self::DATE_FACTOR => $this->dateFactorChanges[$letter]) + $baseurl,
                        $content,
                        array('class' => 'browselink btn btn-default btn-xs', 'title' => $title));
            }
        }

        // Repeating column
        switch ($this->dateType) {
            case 'D':
                // $header = $repeater->period_1;
                $header = $repeater->period_1->call($this->util->getTranslated()->formatDate);
                break;

            case 'W':
                $header = array($repeater->period_1, \MUtil_Html::create()->br(),
                    \MUtil_Lazy::call('sprintf', $this->_('week %s'), $repeater->period_2));
                break;

            case 'M':
                $header = array($repeater->period_1, \MUtil_Html::create()->br(),
                    $repeater->period_2->call($this->util->getLocalized()->getMonthName));
                break;

            case 'Y':
                $header = $repeater->period_1;
                break;

            default:
                throw new \Gems_Exception_Coding('Incorrect date_type value: ' . $this->dateType); //  $this->_getParam('date_type', 'W'));
        }
        $th = $bridge->th();
        $th->class = array($this->dataCellClass, $columnClass);
        $th->a(array(self::DATE_FACTOR => $repeater->date_factor, \MUtil_Model::AUTOSEARCH_RESET => null) + $baseurl,
                $header
                );
        $th->setRepeater($repeater);
        $th->setRepeatTags(true);

        $baseurl[\Gems_Selector_DateSelectorAbstract::DATE_FACTOR] = $repeater->date_factor;
        $baseurl[\Gems_Selector_DateSelectorAbstract::DATE_GROUP]  = null;
        $th->onclick = array('location.href=\'', new \MUtil_Html_HrefArrayAttribute($baseurl), '\';');
    }
}
