<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Agenda\Agenda;
use Gems\Model\AgendaDiagnosisModel;
use Gems\Model\Dependency\ActivationDependency;
use Gems\Snippets\Agenda\CalendarDiagnosisExampleTableSnippet;
use Gems\Snippets\AutosearchFormSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Generic\CurrentSiblingsButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\Util;
use Gems\Util\Translated;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5 09-Oct-2018 13:48:01
 */
class AgendaDiagnosisHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => ['gad_diagnosis_code' => SORT_ASC, 'gad_description' => SORT_ASC],
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['diagnosis', 'diagnoses'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        AutosearchFormSnippet::class,
        ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showParameters = [
        'calSearchFilter' => 'getShowFilter',
        'caption'         => 'getShowCaption',
        'onEmptyAlt'      => 'getShowOnEmpty',
        'sortParamAsc'    => 'asrt',
        'sortParamDesc'   => 'dsrt',
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CurrentButtonRowSnippet::class,
        CalendarDiagnosisExampleTableSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected Agenda $agenda,
        protected Translated $translatedUtil,
        protected Util $util,
        protected readonly AgendaDiagnosisModel $agendaDiagnosisModel
    ) {
        parent::__construct($responder, $translate, $cache);
    }

    /**
     * Cleanup appointments
     */
    public function cleanupAction()
    {
        $params = $this->_processParameters($this->showParameters);
        $params['contentTitle'] = $this->_('Clean up existing appointments?');
        $params['filterOn']     = 'gap_diagnosis_code';
        $params['filterWhen']   = 'gad_filter';

        $snippets = [
            'Generic\\ContentTitleSnippet',
            'Agenda\\AppointmentCleanupSnippet',
            CurrentSiblingsButtonRowSnippet::class,
            'Agenda\\CalendarExampleTableSnippet',
        ];

        $this->addSnippets($snippets, $params);
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
     */
    protected function createModel(bool $detailed, string $action): AgendaDiagnosisModel
    {
        if ($detailed && (! $this->agendaDiagnosisModel->getMetaModel()->hasDependency('gad_active'))) {
            if ($this->responder instanceof GemsSnippetResponder) {
                $menuHelper = $this->responder->getMenuSnippetHelper();
            } else {
                $menuHelper = null;
            }
            $metaModel = $this->agendaDiagnosisModel->getMetaModel();
            $metaModel->addDependency(new ActivationDependency(
                $this->translate,
                $metaModel,
                $menuHelper,
            ));
        }
        return $this->agendaDiagnosisModel;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Agenda diagnoses');
    }

    /**
     * Returns the fields for autosearch with
     *
     * @return array
     */
    public function getSearchFields(): array
    {
        return [
            'gad_coding_method' => $this->_('(all coding systems)'),
            'gad_filter'        => $this->_('(all filters)'),
        ];
    }

    /**
     *
     * @return string
     */
    public function getShowCaption(): string
    {
        return $this->_('Example appointments');
    }

    /**
     *
     * @return string
     */
    public function getShowOnEmpty(): string
    {
        return $this->_('No example diagnosis found');

    }
    /**
     * Get an agenda filter for the current shown item
     *
     * @return array
     */
    public function getShowFilter(): array
    {
        return [
            $this->showParameters['sortParamDesc'] => 'gap_admission_time',
            'gap_diagnosis_code' => $this->_getIdParam(),
            'limit' => 10,
        ];
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('diagnosis', 'diagnoses', $count);
    }
}
