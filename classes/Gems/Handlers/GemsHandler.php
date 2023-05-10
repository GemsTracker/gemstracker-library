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
use Gems\Html;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\CreateAction;
use Gems\SnippetsActions\Form\EditAction;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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
    use PaginatorHandlerTrait;

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

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected array $defaultSearchData = [];

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

    public function __construct(SnippetResponderInterface $responder, MetaModelLoader $metaModelLoader, TranslatorInterface $translate)
    {
        parent::__construct($responder, $metaModelLoader, $translate);
        Html::init();
    }

    /**
     * Get the data to use for searching: the values passed in the request + any defaults
     * used in the search form (or any other search request mechanism).
     *
     * It does not return the actual filter used in the query.
     *
     * @see getSearchFilter()
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array
     */
    public function getSearchData(bool $useRequest = true): array
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
         * @var $session SessionInterface
         */
        $session = $this->request->getAttribute(SessionInterface::class);

        $sessionData = [];
        if ($session->has($sessionId)) {
            $sessionData = $session->get($sessionId);
        }

        $defaults = $this->getSearchDefaults();

        if ($useRequest) {
            $data = $this->request->getQueryParams();
            $data += $this->request->getParsedBody();

            if (isset($data[self::SEARCH_RESET]) && $data[self::SEARCH_RESET]) {
                // Clean up values
                $sessionData = [];

                //$request->setParam(self::SEARCH_RESET, null);
            } else {
                $data = $data + $sessionData;
            }

            // Always remove
            unset($data[self::SEARCH_RESET]);

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
        } else {
            $data = $sessionData;
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
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useRequest = true): array
    {
        if (null !== $this->_searchFilter) {
            return $this->_searchFilter;
        }

        $filter = $this->getSearchData($useRequest);
        $this->_searchFilter = [];

        foreach ($filter as $field => $value) {
            if (isset($this->searchFieldRenames[$field])) {
                $field = $this->searchFieldRenames[$field];
            }

            $this->_searchFilter[$field] = $value;
        }

        // \MUtil\EchoOut\EchoOut::track($this->_searchFilter);

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
        }

        if ($action instanceof BrowseTableAction) {
            $path = $this->requestInfo->getBasePath();
            $action->dynamicSort = $this->getDynamicSortFor($action->sortParamDesc, $action->sortParamAsc);
            $action->pageItems   = $this->getPageItems();
            $action->pageNumber  = $this->getPageNumber();

            if ($action instanceof BrowseFilteredAction) {
                $action->searchFilter = $this->getSearchFilter(true);

                if ($action instanceof BrowseSearchAction) {
                    $action->searchData = $this->getSearchData(true);
                }
            }
        }
        if ($action instanceof CreateAction) {
            $action->class = "formTable";
            $action->addCurrentParent = true;
            $action->addCurrentChildren = false;

            if ($action instanceof EditAction) {
                $action->addCurrentSiblings = true;
            } else {
                $action->addCurrentSiblings = false;
            }
        }
//        if ($action instanceof ExportAction) {
//            $step = $this->requestInfo->getParam('step');
//            if ($step) {
//                $action->step = $step;
//            }
//            $action->formTitle = \ucfirst(sprintf($this->_('%s export'), $this->getTopic(1)));
//        }
    }
}