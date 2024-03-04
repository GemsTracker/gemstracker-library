<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use DateTimeInterface;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Html;
use Gems\Model\Dependency\UsageDependency;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\CreateAction;
use Gems\SnippetsActions\Form\EditAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\User\User;
use Mezzio\Session\SessionInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\Browse\BrowseTableAction;
use Zalt\SnippetsActions\ModelActionInterface;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.9.2
 */
abstract class GemsHandler extends \Zalt\SnippetsHandler\ModelSnippetHandlerAbstract
{
    use CsrfHandlerTrait;
    use PaginatorHandlerTrait;

    /**
     * Url parameter to reset searches
     */
    const AUTOSEARCH_RESET = 'reset';

    /**
     *
     * @var ?array The search data
     */
    private ?array $_searchData = null;

    /**
     *
     * @var ?array The search data
     */
    private ?array $_searchFilter = null;

    public array $cacheTags = [];

    protected bool $checkParameterMaps = true;

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected array $defaultSearchData = [];

    protected static array $parameterMaps = [];

    /**
     * Optional search field renames
     *
     * The optional sharing of searches between action using searchSessionId's means that sometimes
     * the fields in the search have to be renamed for a specific action.
     *
     * @var array
     */
    protected array $searchFieldRenames = [];

    /**
     * An optional search session id.
     *
     * When set, autosearch gets a session memory. Multiple controllers can share one session id
     *
     * @var string
     */
    protected string $searchSessionId = '';

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        protected readonly CacheItemPoolInterface $cache,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate);
        Html::init();
    }

    protected function assertAccessFromOrganization(User $currentUser, int $organizationId): void
    {
        $currentUser->assertAccessToOrganizationId($organizationId);
    }

    protected function getAttributeFilters(MetaModellerInterface $model): array
    {
        $filters = [];
        if (!$this->checkParameterMaps) {
            return $filters;
        }
        $maps = $this->getParameterMaps($model);
        foreach($maps as $attributeName => $fieldName) {
            if ($this->request->getAttribute($attributeName) !== null) {
                $filters[$fieldName] = $this->request->getAttribute($attributeName);
                if ($attributeName === MetaModelInterface::REQUEST_ID2) {
                    $organizationId = (int)$this->request->getAttribute(MetaModelInterface::REQUEST_ID2);
                    /**
                     * @var User $currentUser
                     */
                    $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
                    $this->assertAccessFromOrganization($currentUser, $organizationId);
                }
            }
        }
        return $filters;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return ucfirst($this->getTopic(2));
    }

    protected function getParameterMaps(MetaModellerInterface $model): array
    {
        return static::$parameterMaps + $model->getMetaModel()->getMaps();
    }

    /**
     * Get the data to use for searching: the values passed in the request + any defaults
     * used in the search form (or any other search request mechanism).
     *
     * It does not return the actual filter used in the query.
     *
     * @see getSearchFilter()
     *
     * @param boolean $useSession Use the session as only source (when false, the session is used)
     * @return array
     */
    public function getSearchData(bool $useSession = false): array
    {
        if (is_array($this->_searchData)) {
            return $this->_searchData;
        }

        $sessionId = 'ModelSnippetActionAbstract_getSearchData';
        if ($this->searchSessionId) {
            $sessionId .= $this->searchSessionId;
        } else {
            // Always use a search id
            $sessionId .= get_class($this);
        }

        /**
         * @var SessionInterface $session
         */
        $session = $this->request->getAttribute(SessionInterface::class);

        $sessionData = [];
        if ($session->has($sessionId)) {
            $sessionData = $session->get($sessionId);
        }

        $defaults = $this->getSearchDefaults();

        if ($useSession) {
            $data = $sessionData;
        } else {
            $data = $this->request->getQueryParams();
            $data += $this->request->getParsedBody();

            if (isset($data[self::AUTOSEARCH_RESET]) && $data[self::AUTOSEARCH_RESET]) {
                // Clean up values
                $sessionData = [];

            } else {
                $data = $data + $sessionData;
            }

            // Always remove
            unset($data[self::AUTOSEARCH_RESET]);

            // Store cleaned values in session (we do not store the defaults now as they may change
            // depending on the request and this way the filter data responds to that).
            // On the other hand we do store empty values in the session when they are in the defaults
            // array. The reason is that otherwise a non-empty default can later overrule an empty
            // value.
            $tmp = [];
            foreach ($data as $k => $v) {
                if (is_array($v) || strlen($v) || array_key_exists($k, $defaults)) {
                    $tmp[$k] = $v;
                }
            }
            $session->set($sessionId, $tmp);
        }

        // Add defaults to data without cleanup
        if ($defaults) {
            $data = $data + $defaults;
        }

        // \MUtil\EchoOut\EchoOut::track($data, $this->searchSessionId);

        // Remove empty strings and nulls HERE as they are not part of
        // the filter itself, but the values should be stored in the session.
        //
        // Remove all empty values (but not arrays) from the filter
        $this->_searchData = array_filter($data, function($i) { return is_array($i) || $i instanceof DateTimeInterface || strlen((string)$i); });

        // \MUtil\EchoOut\EchoOut::track($this->_searchData, $this->searchSessionId);

        return $this->_searchData;
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
        return $this->defaultSearchData;
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useSession Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useSession = false): array
    {
        if (null !== $this->_searchFilter) {
            return $this->_searchFilter;
        }

        $filter = $this->getSearchData($useSession);
        $this->_searchFilter = [];

        foreach ($filter as $field => $value) {
            if (isset($this->searchFieldRenames[$field])) {
                $field = $this->searchFieldRenames[$field];
            }

            $this->_searchFilter[$field] = $value;
        }

        // dump($this->_searchFilter);

        return $this->_searchFilter;
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processResponseCookies(parent::handle($request));
    }

    public function prepareAction(SnippetActionInterface $action) : void
    {
        parent::prepareAction($action);

        if ($action instanceof ModelActionInterface) {
            $action->model = $this->getModel($action);
            $action->addToFilter($this->getAttributeFilters($action->model));
        }

        if ($action instanceof BrowseTableAction) {
            $path = $this->requestInfo->getBasePath();
            $action->dynamicSort = $this->getDynamicSortFor($action->sortParamDesc, $action->sortParamAsc);
            $action->onEmpty     = sprintf($this->_('No %s found!'), $this->getTopic());
            $action->pageItems   = $this->getPageItems();
            $action->pageNumber  = $this->getPageNumber();

            if ($action instanceof BrowseFilteredAction) {
                $useSession = $action instanceof ExportAction;
                $action->searchFilter = $this->getSearchFilter($useSession);

                if ($action instanceof BrowseSearchAction) {
                    $action->contentTitle = $this->getIndexTitle();
                    $action->searchData = $this->getSearchData($useSession);
                }
                if ($action instanceof ExportAction) {
                    $action->csrfName = $this->getCsrfTokenName();
                    $action->csrfToken = $this->getCsrfToken($action->csrfName);
                    $step = $this->requestInfo->getParam('step');
                    if ($step) {
                        if (ExportAction::STEP_RESET !== $step) {
                            $action->step = $step;
                        }
                    }
                    $action->formTitle = \ucfirst(sprintf($this->_('%s export'), $this->getTopic(1)));
                }
            }

        } elseif ($action instanceof CreateAction) {
            if ($this->cacheTags) {
                $action->cache = $this->cache;
                $action->cacheTags = $this->cacheTags;
            }
            $action->class = "formTable";
            $action->csrfName = $this->getCsrfTokenName();
            $action->csrfToken = $this->getCsrfToken($action->csrfName);
            $action->addCurrentParent = true;
            $action->addCurrentChildren = false;
            $action->subjects = [$this->getTopic(1), $this->getTopic(2)];

            if ($action instanceof EditAction) {
                $action->addCurrentSiblings = true;
            } else {
                $action->addCurrentSiblings = false;
            }

        } elseif ($action instanceof ShowAction) {
            $action->contentTitle = sprintf($this->_('Showing %s'), $this->getTopic(1));
            $action->subjects = [$this->getTopic(1), $this->getTopic(2)];

        } elseif ($action instanceof DeleteAction) {
            $action->contentTitle = sprintf($this->_('Delete %s'), $this->getTopic(1));
            $action->csrfName = $this->getCsrfTokenName();
            $action->csrfToken = $this->getCsrfToken($action->csrfName);
            $action->subjects = [$this->getTopic(1), $this->getTopic(2)];
        }

        if (property_exists($this, 'usageCounter') && property_exists($action, 'usageCounter')) {
            $action->usageCounter = $this->usageCounter;

            if ($this->responder instanceof GemsSnippetResponder) {
                $menuHelper = $this->responder->getMenuSnippetHelper();
            } else {
                $menuHelper = null;
            }
            $metaModel = $this->getModel($action)->getMetaModel();
            $metaModel->addDependency(new UsageDependency(
                $this->translate,
                $metaModel,
                $this->usageCounter,
                $menuHelper,
            ));
        }
    }
}