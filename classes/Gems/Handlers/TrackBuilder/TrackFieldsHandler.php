<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michel Rooks <info@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Db\ResultFetcher;
use Gems\MenuNew\RouteHelper;
use Gems\Repository\TrackDataRepository;
use Gems\Tracker;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Tracker\Model\TrackModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class TrackFieldsHandler extends TrackMaintenanceWithEngineHandlerAbstract
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
        'extraSort' => ['gtf_id_order' => SORT_ASC],
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Tracker\\Fields\\FieldsTableSnippet'];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['track', 'tracks'];

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $createParameters = [
        'formTitle' => 'getCreateTitle',
    ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = [
        'Tracker\\Fields\\FieldEditSnippet',
        'Agenda\\ApplyFiltersInformation',
        ];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $deleteSnippets = ['Tracker\\Fields\\FieldDeleteSnippet'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Tracker\\Fields\\FieldsTitleSnippet', 'Tracker\\Fields\\FieldsAutosearchForm'];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'Tracker\\Fields\\FieldShowSnippet',
        'Agenda\\ApplyFiltersInformation'
        ];

    public function __construct(
        RouteHelper $routeHelper,
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        Tracker $tracker,
        protected ResultFetcher $resultFetcher,
        protected TrackDataRepository $trackDataRepository,
    ) {
        parent::__construct($routeHelper, $responder, $translate, $tracker);
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
     * @return TrackModel
     */
    public function createModel(bool $detailed, string $action): FieldMaintenanceModel
    {
        $engine = $this->getTrackEngine();
        $model  = $engine->getFieldsMaintenanceModel($detailed, $action);

        return $model;
    }

    /**
     * Helper function to get the question for the delete action.
     *
     * @return $string
     */
    public function getDeleteQuestion(): string
    {
        $field = $this->request->getAttribute('fid');
        if (FieldMaintenanceModel::APPOINTMENTS_NAME === $this->request->getAttribute('sub')) {
            $used  = $this->resultFetcher->fetchOne(
                    "SELECT COUNT(*)
                        FROM gems__respondent2track2appointment
                        WHERE gr2t2a_id_app_field = ? AND gr2t2a_id_appointment IS NOT NULL",
                    [$field]
                    );
        } else {
            $used  = $this->resultFetcher->fetchOne(
                    "SELECT COUNT(*)
                        FROM gems__respondent2track2field
                        WHERE gr2t2f_id_field = ? AND gr2t2f_value IS NOT NULL",
                    [$field]
                    );
        }

        if (! $used) {
            return $this->_('Do you want to delete this field?');
        }

        $messenger = $this->request->getAttribute(StatusMessengerInterface::class);

        $messenger->addMessage(sprintf($this->plural(
                'This field will be deleted from %s assigned track.',
                'This field will be deleted from %s assigned tracks.',
                $used), $used));

        return sprintf($this->plural(
                'Do you want to delete this field and the value stored for the field?',
                'Do you want to delete this field and the %s values stored for the field?',
                $used), $used);
    }

    /**
     * Helper function to get the title for the create action.
     *
     * @return $string
     */
    public function getCreateTitle(): string
    {
        return sprintf(
                $this->_('New field for %s track...') ,
                $this->trackDataRepository->getTrackTitle($this->_getIdParam())
                );
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return sprintf($this->_('Fields %s'), $this->trackDataRepository->getTrackTitle($this->_getIdParam()));
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('field', 'fields', $count);
    }
}