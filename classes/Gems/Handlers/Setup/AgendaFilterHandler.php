<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Agenda\Agenda;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 15-okt-2014 23:30:18
 */
class AgendaFilterHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var array of snippets name
     */
    protected array $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => ['gaf_id_order' => SORT_ASC],
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['appointment_filters'];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var array of snippets name
     */
    protected array $createEditSnippets = [
        'ModelFormSnippet',
        'Agenda\\ApplyFiltersInformation',
        ];

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected array $defaultSearchData = ['gaf_active' => 1];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Tracker\\Fields\\FilterSearchFormSnippet'];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var array of snippets name
     */
    protected array $indexStopSnippets = [
        'Generic\\CurrentSiblingsButtonRowSnippet',
        'Agenda\\ApplyFiltersInformation',
    ];

    /**
     * The snippets used for the show action
     *
     * @var array of snippets name
     */
    protected array $showParameters = [
        'calSearchFilter' => 'getShowFilter',
        'browse' => true,
        ];

    /**
     * The snippets used for the show action
     *
     * @var array of snippets name
     */
    protected array $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippet',
        'Agenda\\EpisodeTableSnippet',
        'Agenda\\CalendarTableSnippet',
        'Agenda\\FilterSqlSnippet',
        'Agenda\\ApplyFiltersInformation',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected Agenda $agenda,
    )
    {
        parent::__construct($responder, $translate);
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
    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        $model = $this->agenda->newFilterModel();

        if ($detailed) {
            if (('edit' == $action) || ('create' == $action)) {
                $model->applyEditSettings(('create' == $action));
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Appointment filters');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('appointment filter', 'appointment filters', $count);
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useRequest = true): array
    {
        $filter = parent::getSearchFilter($useRequest);

        if (isset($filter['used_in_track'])) {
            switch ($filter['used_in_track']) {
                case -1:
                    $filter[] = "NOT EXISTS (SELECT gtap_filter_id FROM gems__track_appointments WHERE gtap_filter_id = gaf_id)";
                    break;
                case -2:
                    $filter[] = "gaf_id IN (SELECT gtap_filter_id FROM gems__track_appointments)";
                    break;
                case -3:
                    $filter[] = "gaf_id IN (SELECT gtap_filter_id FROM gems__track_appointments WHERE gtap_create_track = 0)";
                    break;
                case -4:
                    $filter[] = "gaf_id IN (SELECT gtap_filter_id FROM gems__track_appointments WHERE gtap_create_track > 0)";
                    break;
                default:
                    $filter[] = sprintf(
                            "gaf_id IN (SELECT gtap_filter_id FROM gems__track_appointments WHERE gtap_id_track = %d)",
                            intval($filter['used_in_track'])
                            );
                    break;
            }
            unset($filter['used_in_track']);
        }
        if (isset($filter['creates_track'])) {
            switch ($filter['creates_track']) {
                case -1:
                    $filter[] = "gaf_id IN (SELECT gtap_filter_id FROM gems__track_appointments WHERE gtap_create_track > 0)";
                    break;
                default:
                    $filter[] = sprintf(
                            "gaf_id IN (SELECT gtap_filter_id FROM gems__track_appointments WHERE gtap_create_track = %d)",
                            intval($filter['creates_track'])
                            );
                    break;
            }
            unset($filter['creates_track']);
        }

        if (isset($filter['used_in_filter'])) {
            $model = $this->getModel();
            switch ($filter['used_in_filter']) {
                case -1:
                    $sub      = $model->getSubFilterSql('*');
                    $filter[] = "NOT EXISTS $sub";
                    break;
                case -2:
                    $sub      = $model->getSubFilterSql('*');
                    $filter[] = "EXISTS $sub";
                    break;

                default:
                    $sub      = $model->getSubFilterIdSql($filter['used_in_filter'], "other.gaf_id");
                    $filter[] = "gaf_id IN ($sub)";
                    break;
            }
            // \MUtil\Model::$verbose = true;
            unset($filter['used_in_filter']);
        }

        return $filter;
    }

    /**
     * Get an agenda filter for the current shown item
     *
     * @return AppointmentFilterInterface or false if not found
     */
    public function getShowFilter()
    {
        $filter = $this->agenda->getFilter($this->_getIdParam());

        if ($filter) {
            return $filter;
        }

        return false;
    }
}
