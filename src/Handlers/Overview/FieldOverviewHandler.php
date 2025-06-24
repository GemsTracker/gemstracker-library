<?php

namespace Gems\Handlers\Overview;

/**
 * @package    Gems
 * @subpackage Handlers\Overview
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

use Gems\Legacy\CurrentUserRepository;
use Gems\Model\Type\GemsDateTimeType;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Tracker\Compliance\ComplianceSearchFormSnippet;
use Gems\Snippets\Tracker\Fields\FieldOverviewTableSnippet;
use Gems\Tracker;
use Gems\User\Mask\MaskRepository;
use MUtil\Model\DatabaseModelAbstract;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers\Overview
 * @since      Class available since version 2.0
 */
class FieldOverviewHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
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
    protected array $autofilterParameters = [
        'extraFilter' => ['grc_success' => 1],
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [ 
        FieldOverviewTableSnippet::class,
        ];

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *  We don't want the filler to show as it is irrelevant to this overview
     */
    protected array $indexParameters = array(
        'addCurrentChildren' => true,
        'addCurrentSiblings' => true,
        'showFiller'    => false
    );

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = array(
        ContentTitleSnippet::class,
        ComplianceSearchFormSnippet::class,
        );

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected MaskRepository $maskRepository,
        protected PeriodSelectRepository $periodSelectRepository,
        protected TrackDataRepository $trackDataRepository,
        protected Tracker $tracker,
    )
    {
        parent::__construct($responder, $translate, $cache);

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
     * @return DataReaderInterface
     */
    public function createModel($detailed, $action): DataReaderInterface
    {
        $model = new \Gems\Model\MaskedModel('track_fields' , 'gems__respondent2track');
        $model->setMaskRepository($this->maskRepository);

        $model->addTable('gems__respondent2org', array(
            'gr2t_id_user' => 'gr2o_id_user',
            'gr2t_id_organization' => 'gr2o_id_organization'
            ));
        $model->addTable('gems__respondents', array('gr2o_id_user' => 'grs_id_user'));
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__reception_codes', array('gr2t_reception_code' => 'grc_id_reception_code'));

        $model->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');

        $model->resetOrder();
        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));
        if (! $this->maskRepository->isFieldMaskedPartial('respondent_name')) {
            $model->set('respondent_name', 'label', $this->_('Name'));
        }
        $model->set('gr2t_start_date', 'label', $this->_('Start date'));
        $model->set('gr2t_end_date',   'label', $this->_('End date'));

        $filter = $this->getSearchFilter($action !== 'export');
        if (! (isset($filter['gr2t_id_organization']) && $filter['gr2t_id_organization'])) {
            $this->autofilterParameters['extraFilter']['gr2t_id_organization'] = $this->currentUser->getRespondentOrgFilter();
        } else {
            $this->currentUserRepository->assertAccessToOrganizationId($filter['gr2t_id_organization']);
        }
        if (! (isset($filter['gr2t_id_track']) && $filter['gr2t_id_track'])) {
            $this->autofilterParameters['extraFilter'][] = DatabaseModelAbstract::WHERE_NONE;
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
            return $model;
        }

        // Add the period filter - if any
        $type  = new GemsDateTimeType($this->translate);
        $where = $this->periodSelectRepository->createPeriodFilter($filter, $type->dateFormat, $type->storageFormat, $this->getSearchDefaults());
        if ($where) {
            $this->autofilterParameters['extraFilter'][] = $where;
        }

        $trackId = $filter['gr2t_id_track'];
        $engine = $this->tracker->getTrackEngine($trackId);
        $engine->addFieldsToModel($model, false);

        // Add masking if needed
        $model->applyMask();

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Respondent Track fields');
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

        if (!isset($this->defaultSearchData['gr2t_id_track'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $tracks = $this->trackDataRepository->getTracksForOrgs($orgs);
            if (count($tracks) == 1) {
                $this->defaultSearchData['gr2t_id_track'] = key($tracks);
            }
        }

        return parent::getSearchDefaults();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('track field', 'track fields', $count);
    }
}
