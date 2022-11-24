<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Gems\MenuNew\RouteHelper;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Generic\CurrentSiblingsButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\ModelFormSnippet;
use Gems\Snippets\ModelItemYesNoDeleteSnippet;
use Gems\Snippets\ModelTableSnippet;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use MUtil\Model\ModelAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.9.2
 */
abstract class ModelSnippetLegacyHandlerAbstract extends \MUtil\Handler\ModelSnippetLegacyHandlerAbstract
{
    /**
     * \Gems only parameters used for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private array $_autofilterExtraParameters = [
        'browse'        => true,
        'containingId'  => 'autofilter_target',
        'onEmpty'       => 'getOnEmptyText',
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        'menuActionController'  => 'getControllerName',
        'requestQueryParams'    => 'getRequestQueryParams',
        'requestPost'           => 'getRequestParsedBody',
        'isPost'                => 'getRequestIsPost'
    ];

    /**
     * \Gems only parameters used for the create action. Can be overruled
     * by setting $this->createParameters or $this->createEditParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private array $_createExtraParameters = [
        'formTitle'     => 'getCreateTitle',
        'topicCallable' => 'getTopicCallable',
        'csrfGuard'     => 'getCsrfGuard',
    ];

    /**
     * \Gems only parameters used for the deactivate action. Can be overruled
     * by setting $this->deactivateParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private array $_deactivateExtraParameters = [
        'confirmQuestion' => 'getDeactivateQuestion',
        'displayTitle'    => 'getDeactivateTitle',
        'formTitle'       => 'getDeactivateTitle',
        'topicCallable'   => 'getTopicCallable',
    ];

    /**
     * \Gems only parameters used for the delete action. Can be overruled
     * by setting $this->deleteParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private array $_deleteExtraParameters = [
        'deleteQuestion' => 'getDeleteQuestion',
        'displayTitle'   => 'getDeleteTitle',
        'formTitle'      => 'getDeleteTitle',
        'topicCallable'  => 'getTopicCallable',
    ];

    /**
     * \Gems only parameters used for the edit action. Can be overruled
     * by setting $this->editParameters or $this->createEditParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private array $_editExtraParameters = [
        'formTitle'     => 'getEditTitle',
        'topicCallable' => 'getTopicCallable',
        'csrfGuard'     => 'getCsrfGuard',
    ];

    /**
     * \Gems only parameters used for the export action. Can be overruled
     * by setting $this->editParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private array $_exportExtraParameters = [
        'exportClasses' => 'getExportClasses',
    ];

    /**
     * \Gems only parameters used for the import action. Can be overruled
     * by setting $this->inmportParameters
     *
     * @var array Mixed key => value array for snippet initializPdfation
     */
    private array $_importExtraParameters = [
        'formatBoxClass'   => 'browser table',
        'importer'         => 'getImporter',
        'tempDirectory'    => 'getImportTempDirectory',
        'topicCallable'    => 'getTopic',
    ];

    /**
     * \Gems only parameters used for the deactivate action. Can be overruled
     * by setting $this->deactivateParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private array $_reactivateExtraParameters = [
        'confirmQuestion' => 'getReactivateQuestion',
        'displayTitle'    => 'getReactivateTitle',
        'formTitle'       => 'getReactivateTitle',
        'topicCallable'   => 'getTopicCallable',
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    // protected $autofilterSnippets = 'ModelTableSnippet';

    /**
     * @var int User id from request
     */
    protected int $currentUserId = 1;

    /**
     * The snippets used for the autofilter action.
     *
     * @var array snippets name
     */
    protected array $autofilterSnippets = [
        ModelTableSnippet::class,
        ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = [
        ModelFormSnippet::class,
        CurrentButtonRowSnippet::class,
        ];

    /**
     * The parameters used for the delete action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $deleteParameters = [
        'abortUrl' => 'getShowUrl',
        'afterDeleteUrl' => 'getIndexUrl'
        ];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $deleteSnippets = [
        ModelItemYesNoDeleteSnippet::class
        ];

    /**
     * The snippets used for the export action
     *
     * @var mixed String or array of snippets name
     */
    protected array $exportFormSnippets = ['Export\\ExportFormSnippet'];

    /**
     * The parameters used for the export actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $exportParameters = [];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        'Generic\\ContentTitleSnippet', 
        'AutosearchFormSnippet',
        ];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var array String or array of snippets name
     */
    protected array $indexStopSnippets = [
        CurrentButtonRowSnippet::class,
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
        ];

    /**
     * Array of the actions that use a summarized version of the model.
     *
     * This determines the value of $detailed in createAction(). As it is usually
     * less of a problem to use a $detailed model with an action that should use
     * a summarized model and I guess there will usually be more detailed actions
     * than summarized ones it seems less work to specify these.
     *
     * @var array $summarizedActions Array of the actions that use a
     * summarized version of the model.
     */
    public array $summarizedActions = ['index', 'autofilter', 'export'];

    public function __construct(
        protected RouteHelper $routeHelper,
        SnippetResponderInterface $responder,
        TranslatorInterface $translate)
    {
        parent::__construct($responder, $translate);
        \Gems\Html::init();
    }
    
    /**
     * The automatically filtered result
     *
     * @param bool $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction(bool $resetMvc = true): void
    {
        //$htmlOrig = $this->html;
        //$div      = $this->html->div(array('id' => 'autofilter_target', 'class' => 'table-container'));

        // Already done when this value is false
        if ($resetMvc) {
            $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;
        }

        // $this->html = $div;
        parent::autofilterAction($resetMvc);
        // $this->html = $htmlOrig;
    }

    /**
     * Action for showing a create new item page with extra title
     */
    public function createAction(): void
    {
        $this->createEditParameters = $this->createEditParameters + $this->_createExtraParameters;

        parent::createAction();
    }

    /**
     * Action for showing a deactivate item page with extra titles
     */
    public function deactivateAction(): void
    {
        $this->deactivateParameters = $this->deactivateParameters + $this->_deactivateExtraParameters;

        parent::deactivateAction();
    }

    /**
     * Action for showing a delete item page with extra titles
     */
    public function deleteAction(): void
    {
        $this->deleteParameters = $this->deleteParameters + $this->_deleteExtraParameters;

        parent::deleteAction();
    }

    /**
     * Action for showing a edit item page with extra title
     */
    public function editAction(): void
    {
        $this->createEditParameters = $this->createEditParameters + $this->_editExtraParameters;

        parent::editAction();
    }

    /**
     * Export model data
     */
    public function exportAction(): void
    {
        // TODO Add export action to snippet
    }


    public function getCsrfGuard(): ?CsrfGuardInterface
    {
        return $this->request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
    }

    public function getActionUrl(string $action): ?string
    {
        $parentRoute = $this->routeHelper->getRouteParent($this->requestInfo->getRouteName(), $action);
        // file_put_contents('data/logs/echo.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . $this->requestInfo->getRouteName() . " -> " . $parentRoute['name'] . "\n", FILE_APPEND);        
        if (null === $parentRoute) {
            return null;
        }
        return $this->routeHelper->getRouteUrl($parentRoute['name'], $this->requestInfo->getParams());
    }

    public function getControllerName(): ?string
    {
        return $this->requestInfo->getCurrentController();
    }

    /**
     * Helper function to get the title for the create action.
     *
     * @return string
     */
    public function getCreateTitle(): string
    {
        return sprintf($this->_('New %s...'), $this->getTopic(1));
    }

    /**
     * Helper function to get the question for the deactivate action.
     *
     * @return string
     */
    public function getDeactivateQuestion(): string
    {
        return sprintf($this->_('Do you want to deactivate this %s?'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the deactivate action.
     *
     * @return string
     */
    public function getDeactivateTitle(): string
    {
        return sprintf($this->_('Deactivate %s'), $this->getTopic(1));
    }

    /**
     * Helper function to get the question for the delete action.
     *
     * @return string
     */
    public function getDeleteQuestion(): string
    {
        return sprintf($this->_('Do you want to delete this %s?'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the delete action.
     *
     * @return string
     */
    public function getDeleteTitle(): string
    {
        return sprintf($this->_('Delete %s'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return string
     */
    public function getEditTitle(): string
    {
        return sprintf($this->_('Edit %s'), $this->getTopic(1));
    }

    /**
     * Get the model for export and have the option to change it before using for export
     * @return
     */
    protected function getExportModel(): ModelAbstract
    {
        $model = $this->getModel();
        $noExportColumns = $model->getColNames('noExport');
        foreach($noExportColumns as $colName) {
            $model->remove($colName, 'label');
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
        return ucfirst((string) $this->getTopic(100));
    }

    public function getIndexUrl(): ?string
    {
        return $this->getActionUrl('index');
    }

    /**
     * Return the current request ID, if any.
     *
     * Overrule this function if the last item in the page title
     * should be something other than te value of
     * \MUtil\Model::REQUEST_ID.
     *
     * @return mixed
     */
    public function getInstanceId(): mixed
    {
        return $this->request->getAttribute(\MUtil\Model::REQUEST_ID);
    }

    /**
     * Returns the on empty texts for the autofilter snippets
     *
     * @return string
     */
    public function getOnEmptyText(): string
    {
        return sprintf($this->_('No %s found...'), $this->getTopic(0));
    }

    /**
     * Helper function to get the question for the reactivate action.
     *
     * @return string
     */
    public function getReactivateQuestion(): string
    {
        return sprintf($this->_('Do you want to reactivate this %s?'), $this->getTopic(1));
    }

    /**
     * Helper function to get the title for the reactivate action.
     *
     * @return string
     */
    public function getReactivateTitle(): string
    {
        return sprintf($this->_('Reactivate %s'), $this->getTopic(1));
    }

    public function getRequestIsPost(): bool
    {
        return $this->requestInfo->isPost();
    }

    public function getRequestParsedBody(): array
    {
        return $this->request->getParsedBody();
    }

    public function getRequestQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * Helper function to get the title for the show action.
     *
     * @return string
     */
    public function getShowTitle(): string
    {
        return sprintf($this->_('Showing %s'), $this->getTopic(1));
    }

    public function getShowUrl(): ?string
    {
        return $this->getActionUrl('show');
    }

    /**
     * Returns the current html/head/title for this page.
     *
     * If the title is an array the seperator concatenates the parts.
     *
     * @param string $separator
     * @return string
     */
    public function getTitle(string $separator = null): string
    {
        return '';
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('item', 'items', $count);
    }

    /**
     * Get a callable for the gettopic function
     * @return callable
     */
    public function getTopicCallable(): callable
    {
        return [$this, 'getTopic'];
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->currentUserId = $request->getAttribute('userId', $this->currentUserId);
        
        return parent::handle($request); 
    }

    /**
     * Generic model based import action
     */
    public function importAction(): void
    {
        $this->importParameters = $this->importParameters + $this->_importExtraParameters;

        parent::importAction();
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction(): void
    {
        $this->autofilterParameters = $this->autofilterParameters + $this->_autofilterExtraParameters;
        if (! isset($this->indexParameters['contentTitle'])) {
            $this->indexParameters['contentTitle'] = $this->getIndexTitle();
        }

        parent::indexAction();
    }

    /**
     * Intializes the html component.
     *
     * @param boolean $reset Throws away any existing html output when true
     * @return void
     */
    public function initHtml(bool $reset = false): void
    {
        if (! $this->html) {
            \Gems\Html::init();
        }
    }

    /**
     * Action for showing a reactivate item page with extra titles
     */
    public function reactivateAction(): void
    {
        $this->reactivateParameters = $this->reactivateParameters + $this->_reactivateExtraParameters;

        parent::reactivateAction();
    }

    /**
     * Action for showing an item page with title
     */
    public function showAction(): void
    {
        if (! isset($this->showParameters['contentTitle'])) {
            $this->showParameters['contentTitle'] = $this->getShowTitle();
        }

        parent::showAction();
    }
}