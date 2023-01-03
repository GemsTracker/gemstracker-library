<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Overview;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Tracker\Fields\FieldReportSearchSnippet;
use Gems\Tracker;
use Gems\Tracker\Model\FieldDataModel;
use MUtil\Model\DatabaseModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 30-nov-2014 17:50:22
 */
class FieldReportHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
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
    protected array $autofilterParameters = array(
        'browse' => false,
        'columns' => 'getBrowseColumns',
        );

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;
    
    /**
     * Where statement filtering on track start / end dates
     *
     * @var string
     */
    protected $dateWhere;

    /**
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $engine;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        FieldReportSearchSnippet::class,
    ];

    /**
     * Where statement filtering out organizations
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

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected ResultFetcher $resultFetcher,
        protected TrackDataRepository $trackDataRepository,
        protected Tracker $tracker,
    )
    {
        parent::__construct($responder, $translate);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action): DataReaderInterface
    {
        $filter = $this->getSearchFilter($action !== 'export');

        // Return empty model when no track selected
        if (! (isset($filter['gtf_id_track']) && $filter['gtf_id_track'])) {
            $model = new \Gems\Model\JoinModel('trackfields' , 'gems__track_fields');
            $model->set('gtf_field_name', 'label', $this->_('Name'));
            $this->autofilterParameters['extraFilter'][] = DatabaseModelAbstract::WHERE_NONE;
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');

            return $model;
        }

        $this->trackId = $filter['gtf_id_track'];

        $this->engine = $this->tracker->getTrackEngine($this->trackId);

        $orgs         = $this->currentUser->getRespondentOrgFilter();
        if (isset($filter['gr2t_id_organization'])) {
            $orgs = array_intersect($orgs, (array) $filter['gr2t_id_organization']);
        }
        $this->orgWhere = " AND gr2t_id_organization IN (" . implode(", ", $orgs) . ")";

        $sql     = "SELECT COUNT(*)
            FROM gems__respondent2track INNER JOIN gems__reception_codes ON gr2t_reception_code = grc_id_reception_code
            WHERE gr2t_id_track = ? AND grc_success = 1" . $this->orgWhere;
        
        // Add the period filter - if any
        if ($where = \Gems\Snippets\AutosearchFormSnippet::getPeriodFilter($filter, $this->resultFetcher->getPlatform())) {
            $sql .= ' AND ' . $where;
        }
        $this->dateWhere = $where; 

        $this->trackCount = $this->resultFetcher->fetchOne($sql, [$this->trackId]);

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
     * A ModelAbstract->setOnLoad() function that takes care of transforming a value
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return string
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
     * A ModelAbstract->setOnLoad() function that takes care of transforming a value
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return string
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
        
        // Add the period filter - if any
        if ($this->dateWhere) {
            $sql .= ' AND ' . $this->dateWhere;
        }

        // \MUtil\EchoOut\EchoOut::track($sql);
        $this->trackFilled = $this->resultFetcher->fetchOne($sql);

        $value = $this->trackFilled;
        return sprintf(
                $this->_('%d (%d%%)'),
                $value,
                $this->trackCount ? round($value / $this->trackCount * 100, 0) : 0
            );
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a value
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return string
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
        
        // Add the period filter - if any
        if ($this->dateWhere) {
            $sql .= ' AND ' . $this->dateWhere;
        }

        // \MUtil\EchoOut\EchoOut::track($sql);
        $value = $this->resultFetcher->fetchOne($sql);
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
    public function getBrowseColumns(): array
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
    public function getIndexTitle(): string
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
    public function getSearchDefaults(): array
    {
        if (! isset($this->defaultSearchData['gr2t_id_organization'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $this->defaultSearchData['gr2t_id_organization'] = array_keys($orgs);
        }
        
        if (!isset($this->defaultSearchData['gtf_id_track'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $tracks = $this->trackDataRepository->getTracksForOrgs($orgs);
            if (\count($tracks) == 1) {
                $this->defaultSearchData['gtf_id_track'] = key($tracks);
            }
        }

        return parent::getSearchDefaults();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('track', 'tracks', $count);
    }
}
