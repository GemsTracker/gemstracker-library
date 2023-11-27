<?php

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Selector;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

use Gems\Handlers\GemsHandler;
use Gems\Menu\RouteHelper;
use Gems\Util\Localized;
use Gems\Util\Translated;
use MUtil\Model\Transform\RequiredRowsTransformer;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HrefArrayAttribute;
use Zalt\Late\Late;
use Zalt\Late\RepeatableInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsHandler\SnippetHandler;

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class DateSelectorAbstract
{
    const DATE_FACTOR = 'df';
    const DATE_GROUP  = 'dg';
    const DATE_TYPE   = 'dt';

    /**
     * @var array $_fields
     */
    private array $_fields = [];

    private $_model;

    /**
     *
     * @var string
     */
    protected string $dataCellClass = 'centerAlign timeResult';

    /**
     * The name of the database table to use as the main table.
     *
     * @var string
     */
    protected string $dataTableName;

    /**
     * The date the current period ends
     *
     * @var DateTimeInterface
     */
    protected ?DateTimeInterface $dateCurrentEnd = null;

    /**
     * The date the current period starts
     *
     * @var DateTimeInterface
     */
    protected ?DateTimeInterface $dateCurrentStart = null;

    /**
     * The number of dateTypes in the future or (when negative) before now.
     *
     * @var integer
     */
    protected ?int $dateFactor = null;

    /**
     * Stores the dateFactor's to use so that the current period will be roughly the same
     * as the current period.
     *
     * Assigned by createModel() SHOULD BECOME PRIVATE
     *
     * @var array
     */
    protected array $dateFactorChanges = [];

    /**
     * The name of the field where the date is calculated from
     *
     * @var string
     */
    protected string $dateFrom;

    /**
     * The group (row) of data selected.
     *
     * @var integer
     */
    protected ?string $dateGroup = null;

    /**
     * The number of periods shown before and after the current period.
     *
     * @var integer
     */
    protected int $dateRange = 3;

    /**
     * Character D W M Y for one of the date types.
     *
     * @var string
     */
    protected string $dateType = 'W';
    
    protected RequestInfo $requestInfo; 

    public function __construct(
        protected TranslatorInterface $translate,
        protected Localized $localized,
        protected \Zend_Db_Adapter_Abstract $db,
        protected RouteHelper $routeHelper,
        protected Translated $translatedUtil,
    )
    {}

    /**
     *
     * @param string $name
     * @return \Gems\Selector\SelectorField
     */
    protected function addField($name)
    {
        $field = new SelectorField($name);

        $this->_fields[$name] = $field;

        return $field;
    }

    /**
     * Creates the base model.
     *
     * @return \MUtil\Model\SelectModel
     */
    protected function createModel(): DataReaderInterface
    {
        $groupby['period_1'] = new \Zend_Db_Expr("YEAR($this->dateFrom)");

        $date = new DateTimeImmutable();

        switch ($this->dateType) {
            case 'D':
                $keyCount = 1;
                $groupby['period_1'] = new \Zend_Db_Expr("CONVERT($this->dateFrom, DATE)");

                $intervalInt = ($this->dateFactor - $this->dateRange);
                $interval = new DateInterval('P' . abs($intervalInt) . 'D');
                if ($intervalInt < 0) {
                    $interval->invert = 1;
                }
                $date = $date->setTime(0, 0, 0)->add($interval);
                $lAdd = new DateInterval('P1D');

                $start = $date->format('c');
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = $date->setTime(23,59,59);
                    }

                    $values = array();
                    $values['period_1'] = $date->format('Y-m-d');
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date = $date->add($lAdd);
                }
                $end = $date->sub(new DateInterval('PT1S'))->format('c');
                break;

            case 'W':
                $keyCount = 2;

                // Use MONDAY as start of week
                $groupby['period_1'] = new \Zend_Db_Expr("substr(YEARWEEK(gto_valid_from, 3),1,4)");
                //$groupby['period_1'] = new \Zend_Db_Expr("YEAR($this->dateFrom) - CASE WHEN WEEK($this->dateFrom, 1) = 0 THEN 1 ELSE 0 END");
                $groupby['period_2'] = new \Zend_Db_Expr("WEEK($this->dateFrom, 3)");

                $intervalInt = (7 * ($this->dateFactor - $this->dateRange));
                $interval  = new DateInterval('P' . abs($intervalInt) . 'D');
                if ($intervalInt < 0) {
                    $interval->invert = 1;
                }
                $date = $date->setTime(0, 0, 0)->modify('this monday')
                    ->add($interval);
                $lAdd = new DateInterval('P7D');
                $lSub = new DateInterval('PT1S');

                $start = $date->format('c');
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = $date->add($lAdd)->sub($lSub);
                    }

                    $values = array();
                    $values['period_1'] = (int) $date->format('Y');
                    $values['period_2'] = (int) $date->format('W');   // Use constant but drop leading zero
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date = $date->add($lAdd);
                }
                $end = $date->sub($lSub)->format('c');
                break;

            case 'M':
                $keyCount = 2;
                $groupby['period_2'] = new \Zend_Db_Expr("MONTH($this->dateFrom)");

                $intervalInt = ($this->dateFactor - $this->dateRange);
                $interval = new DateInterval('P' . abs($intervalInt) . 'M');
                if ($intervalInt < 0) {
                    $interval->invert = 1;
                }
                $date = $date->setTime(0, 0, 0)->modify('first day of this month')
                             ->add($interval);
                $lAdd = new DateInterval('P1M');
                $lSub = new DateInterval('PT1S');

                $start = $date->format('c');
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = $date->add($lAdd)->sub($lSub);
                    }

                    $values = array();
                    $values['period_1'] = $date->format('Y');
                    $values['period_2'] = $date->format('m');
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date = $date->add($lAdd);
                }
                $end = $date->sub($lSub)->format('c');
                break;

            case 'Y':
                $keyCount = 1;

                $intervalInt = ($this->dateFactor - $this->dateRange);
                $interval = new DateInterval('P' . abs($intervalInt) . 'Y');
                if ($intervalInt < 0) {
                    $interval->invert = 1;
                }
                $date = $date->setTime(0, 0, 0)->modify('first day of January this year')
                             ->add($interval);
                $lAdd = new DateInterval('P1Y');
                $lSub = new DateInterval('PT1S');

                $start = $date->format('c');
                for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
                    if (0 == $i) {
                        $this->dateCurrentStart = clone $date;
                        $this->dateCurrentEnd   = clone $date;
                        $this->dateCurrentEnd   = $date->add($lAdd)->sub($lSub);
                    }

                    $values = array();
                    $values['period_1'] = $date->format('Y');
                    $values['range']    = $i;
                    $requiredRows[$i]   = $values;
                    $date = $date->add($lAdd);
                }
                $end = $date->sub($lSub)->format('c');
                break;

            default:
                throw new \Gems\Exception\Coding('Incorrect date_type value: ' . $this->dateType);
        }
        $where = "$this->dateFrom BETWEEN '$start' AND '$end'";

        for ($i = -$this->dateRange; $i <= $this->dateRange; $i++) {
            $requiredRows[$i]['date_factor'] = $this->dateFactor + $i;
            $requiredRows[$i]['df_link']     = null;
            $requiredRows[$i]['df_label']    = null;
        }
        if ($this->dateRange > 0) {
            $requiredRows[-$this->dateRange]['df_link']  = $this->dateFactor - ($this->dateRange * 2);
            $requiredRows[-$this->dateRange]['df_label'] = $this->translate->_('<<');
            $requiredRows[ $this->dateRange]['df_link']  = $this->dateFactor + ($this->dateRange * 2);
            $requiredRows[ $this->dateRange]['df_label'] = $this->translate->_('>>');
            if ($this->dateRange > 1) {
                $i = intval($this->dateRange / 2);
                $requiredRows[-$i]['df_link']  = $this->dateFactor - 1;
                $requiredRows[-$i]['df_label'] = $this->translate->_('<');
                $requiredRows[ $i]['df_link']  = $this->dateFactor + 1;
                $requiredRows[ $i]['df_label'] = $this->translate->_('>');
            }
            $requiredRows[ 0]['df_link']  = $this->dateFactor ? '0' : null;
            $requiredRows[ 0]['df_label'] = $this->translate->_('Now!');
        }

        if ($this->dateFactor) {
            $diff = $this->dateCurrentStart->diff(new DateTimeImmutable());
            $this->dateFactorChanges['D'] = $diff->d;
            $this->dateFactorChanges['W'] = $diff->d * 7;
            $this->dateFactorChanges['M'] = $diff->m;
            $this->dateFactorChanges['Y'] = $diff->y;
        } else {
            $this->dateFactorChanges = array_fill_keys(array('D', 'W', 'M', 'Y'), 0);
        }
        // \MUtil\EchoOut\EchoOut::track($requiredRows);
        // \MUtil\EchoOut\EchoOut::rs($start, $end, $where);

        $select = new \Zend_Db_Select($this->db);
        $select->from($this->dataTableName, $groupby + $this->getDbFields());
        $select->where($where);
        $select->group($groupby);

        $this->processSelect($select);

        // \MUtil\EchoOut\EchoOut::r((string) $select);
        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  (string) $select . "\n", FILE_APPEND);
        $model = new \MUtil\Model\SelectModel($select, $this->dataTableName);

        // Display by column cannot use formatFunction as it is a simple repeater
        // $model->set('duration_avg', 'formatFunction', $this->util->getLocalized()->formatNumber);

        $transformer = new RequiredRowsTransformer();
        $transformer->setDefaultRow($this->getDefaultRow());
        $transformer->setRequiredRows($requiredRows);
        $transformer->setKeyItemCount($keyCount);
        $model->addTransformer($transformer);

        return $model;
    }

    protected function getBaseUrl(): string
    {
        return $this->routeHelper->getRouteUrlOnMatch($this->requestInfo->getRouteName(), $this->requestInfo->getRequestMatchedParams());
    }

    protected function getDateDescriptions()
    {
        return array(
            'D' => $this->translate->_('Show by day'),
            'W' => $this->translate->_('Show by week'),
            'M' => $this->translate->_('Show by month'),
            'Y' => $this->translate->_('Show by year'),
            );
    }

    protected function getDateLabels()
    {
        return array_map('strtolower', array(
            'D' => $this->translate->_('D'),
            'W' => $this->translate->_('W'),
            'M' => $this->translate->_('M'),
            'Y' => $this->translate->_('Y'),
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
     * @return array An array with appropriate default values for use in \MUtil\Model\Transform\RequiredRowsTransformer
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
     * @return \MUtil\Model\ModelAbstract
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
        $newfilter = [];

        if ($this->dateCurrentStart && $this->dateCurrentEnd) {
            if (null === $dateField) {
                $dateField = $this->dateFrom;
            }
            $start = $this->dateCurrentStart->format('c');
            $end   = $this->dateCurrentEnd->format('c');
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

        $bridge->setBaseUrl(array('action' => 'index', 'reset' => null) + $baseurl); // + $model->getFilter();

        $columnClass = Late::iff($repeater->range, null, 'selectedColumn');

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
    public function processSelectorFilter(RequestInfo $requestInfo, array $filter)
    {
        $defaults = $this->getDefaultSearchData();

        $this->dateFactor = $this->processSelectorFilterName(self::DATE_FACTOR, $requestInfo, $filter, $defaults);
        $this->dateGroup  = $this->processSelectorFilterName(self::DATE_GROUP, $requestInfo, $filter, $defaults);
        $this->dateType   = $this->processSelectorFilterName(self::DATE_TYPE, $requestInfo, $filter, $defaults);

        unset($filter[self::DATE_FACTOR], $filter[self::DATE_GROUP], $filter[self::DATE_TYPE]);

        return $filter;
    }

    protected function processSelectorFilterName($name, RequestInfo $requestInfo, array $filter, array $defaults = null)
    {
        if (isset($filter[$name])) {
            return $filter[$name];
        }
        if ($val = $requestInfo->getParam($name)) {
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
    
    public function setRequestInfo(RequestInfo $requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    protected function setTableBody(TableBridge $bridge, RepeatableInterface $repeater, $columnClass)
    {
        $baseurl = $this->getBaseUrl();
        $onEmpty = $this->translate->_('-');

        foreach ($this->getFields() as $name => $field) {
            $bridge->tr(array('class' => ($this->dateGroup == $name) ? 'selectedRow' : null));

            // Left cell
            $td = $bridge->td($field->getLabel());
            $td->class = $field->getLabelClass();

            // Repeating column
            $href = $field->getHRef($repeater, [$baseurl]);
            $td = $bridge->td();
            $td->a($href, $repeater->$name);
            $td->class = array($this->dataCellClass, $field->getClass(), $columnClass);
            $td->setOnEmpty($onEmpty);
            $td->setRepeater($repeater);
            $td->setRepeatTags(true);
        }
    }

    protected function setTableFooter(TableBridge $bridge, RepeatableInterface $repeater, $columnClass)
    {
        $baseurl = $this->getBaseUrl();

        // Empty cell for left column
        $bridge->tf();

        $href = array(
            $baseurl,
            self::DATE_FACTOR => $repeater->df_link,
            GemsHandler::AUTOSEARCH_RESET => null,
            );

        // Repeating column
        $tf = $bridge->tf();
        $tf->class = array($this->dataCellClass, $columnClass);
        $tf->iflink(Late::iff($repeater->df_link, $repeater->df_link->strlen(), 0),
            array('href' => $href, $repeater->df_label, 'class' => 'browselink btn btn-xs'),
            array($repeater->df_label, 'class' => 'browselink btn btn-xs disabled'));
        $tf->setRepeater($repeater);
        $tf->setRepeatTags(true);
    }

    protected function setTableHeader(TableBridge $bridge, RepeatableInterface $repeater, $columnClass)
    {
        $baseurl = $this->getBaseUrl();
        
        // Left cell with period types
        $th = $bridge->th($this->translate->_('Period'), ' ');
        $th->class = 'middleAlign';
        $thdiv = $th->span()->spaced(); // array('class' => 'rightFloat'));
        $contents = $this->getDateLabels();
        foreach ($this->getDateDescriptions() as $letter => $title) {
            if (isset($contents[$letter])) {
                $content = $contents[$letter];
            } else {
                $content = strtolower($this->translate->_($letter));
            }
            if ($letter == $this->dateType) {
                $thdiv->span($content, array('class' => 'browselink btn btn-primary btn-xs disabled'));
            } else {
                $thdiv->a(new HrefArrayAttribute([$baseurl, self::DATE_TYPE => $letter, self::DATE_FACTOR => $this->dateFactorChanges[$letter]]),
                        $content,
                        array('class' => 'browselink btn btn-default btn-xs', 'title' => $title));
            }
        }

        // Repeating column
        switch ($this->dateType) {
            case 'D':
                // $header = $repeater->period_1;
                $header = $repeater->period_1->call($this->translatedUtil->formatDate);
                break;

            case 'W':
                $header = array($repeater->period_1, \Zalt\Html\Html::create()->br(),
                    Late::call('sprintf', $this->translate->_('week %s'), $repeater->period_2));
                break;

            case 'M':
                $header = array($repeater->period_1, \Zalt\Html\Html::create()->br(),
                    $repeater->period_2->call([$this->localized, 'getMonthName']));
                break;

            case 'Y':
                $header = $repeater->period_1;
                break;

            default:
                throw new \Gems\Exception\Coding('Incorrect date_type value: ' . $this->dateType); //  $this->_getParam('date_type', 'W'));
        }
        $th = $bridge->th();
        $th->class = array($this->dataCellClass, $columnClass);
        $th->a(array($baseurl, self::DATE_FACTOR => $repeater->date_factor),
                $header
                );
        $th->setRepeater($repeater);
        $th->setRepeatTags(true);
    }
}
