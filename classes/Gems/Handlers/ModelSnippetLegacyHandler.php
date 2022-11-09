<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Gems\Loader;
use Gems\Project\ProjectSettings;
use Gems\Util;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\RequestInfoFactory;
use Zalt\Base\TranslateableTrait;
use Zalt\Html\Sequence;
use Zalt\Ra\Ra;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.9.2
 */
class ModelSnippetLegacyHandler implements \Psr\Http\Server\RequestHandlerInterface
{
    use TranslateableTrait;
    
    /**
     * Default parameters for the autofilter action. Can be overruled
     * by setting $this->autofilterParameters
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_defaultAutofilterParameters = [
        'searchData'    => 'getSearchData',
        'searchFilter'  => 'getSearchFilter',
    ];

    /**
     * Default parameters for createAction, can be overruled by $this->createParameters
     * or $this->createEditParameters values with the same key.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array
     */
    private $_defaultCreateParameters = [
        'createData' => true,
    ];

    /**
     * Default parameters for editAction, can be overruled by $this->editParameters
     * or $this->createEditParameters values with the same key.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array
     */
    private $_defaultEditParameters = [
        'createData' => false,
    ];

    /**
     * Default parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    private $_defaultImportParameters = [
        'defaultImportTranslator' => 'getDefaultImportTranslator',
        'importTranslators'       => 'getImportTranslators',
    ];

    /**
     * Default parameters for all actions, unless overruled by values with the same key at
     * the action level
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array
     */
    private $_defaultParameters = [
        'cacheTags'             => 'getCacheTags',
        'includeNumericFilters' => 'getIncludeNumericFilters',
        '_messenger'             => 'getMessenger',
        'model'                 => 'getModel',
    ];

    /**
     * Created in createModel().
     *
     * Always retrieve using $this->getModel().
     *
     * $var \MUtil\Model\ModelAbstract $_model The model in use
     */
    private $_model;

    /**
     *
     * @var array The search data
     */
    private $_searchData = false;

    /**
     *
     * @var array The search data
     */
    private $_searchFilter = false;

    /**
     * @var array Local store of parameters
     */
    private $_snippetParams = [];

    /**
     * @var array local store of snippets
     */
    private $_snippetNames = [];

    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialisation
     */
    protected $autofilterParameters = array('columns' => 'getBrowseColumns');

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'ModelTableSnippet';

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public $cacheTags = [];

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = [];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'ModelFormSnippet';

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
    protected $createParameters = [];

    /**
     * Model level parameters used for all actions, overruled by any values set in any other
     * parameters array except the private $_defaultParamters values in this module.
     *
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $defaultParameters = [];

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = [];

    /**
     * The parameters used for the deactivate action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $deactivateParameters = [];

    /**
     * The snippets used for the deactivate  action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deactivateSnippets = 'ModelConfirmDataChangeSnippet';

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
    protected $deleteParameters = [];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'ModelYesNoDeleteSnippet';

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
    protected $editParameters = [];

    /**
     * Array of the actions that use the model in form version.
     *
     * This determines the value of forForm().
     *
     * @var array $formActions Array of the actions that use the model with a form.
     */
    public $formActions = array('create', 'delete', 'edit', 'import');

    /**
     * @var Zalt\Html\Sequence
     */
    protected $html;
    
    /**
     * The parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $importParameters = [];

    /**
     * The snippets used for the import action
     *
     * @var mixed String or array of snippets name
     */
    protected $importSnippets = 'ModelImportSnippet';

    /**
     *
     * @var boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     */
    public $includeNumericFilters = false;

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $indexParameters = [];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = null;

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = null;

    /**
     * The parameters used for the reactivate action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $reactivateParameters = [];

    /**
     * The snippets used for the reactivate action.
     *
     * @var mixed String or array of snippets name
     */
    protected $reactivateSnippets = 'ModelConfirmDataChangeSnippet';

    /**
     * @var \Psr\Http\Message\ServerRequestInterface 
     */
    protected ServerRequestInterface $request;
    
    /**
     * @var \Zalt\Base\RequestInfo 
     */
    protected RequestInfo $requestInfo;
    
    /**
     * Optional search field renames
     *
     * The optional sharing of searches between action using searchSessionId's means that sometimes
     * the fields in the search have to be renamed for a specific action.
     *
     * @var array
     */
    protected $searchFieldRenames = [];

    /**
     * An optional search session id.
     *
     * When set, autosearch gets a session memory. Multiple controllers can share one session id
     *
     * @var string
     */
    protected $searchSessionId;

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = [];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = 'ModelVerticalTableSnippet';

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
    public $summarizedActions = array('index', 'autofilter');

    /**
     *
     * @var boolean $useHtmlView true
     */
    public $useHtmlView = true;  // Overrule parent
    
    public function __construct(
        protected ProjectSettings $project,
        protected Loader $loader,
        protected SnippetResponderInterface $responder, 
        protected SnippetLoader $snippetLoader,
        protected Util $util,         
        TranslatorInterface $translate) 
    {
        $this->translate = $translate;

        $this->html = new Sequence();
        $this->_snippetParams['htmlContent'] = $this->html;
    }

    /**
     * The request ID value
     *
     * @return ?string The request ID value
     */
    protected function _getIdParam(): ?string
    {
        return $this->requestInfo->getParam(\MUtil\Model::REQUEST_ID);
    }

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processParameters(array $input)
    {
        $output = [];

        foreach ($input + $this->defaultParameters + $this->_defaultParameters as $key => $value) {
            if (is_string($value) && method_exists($this, $value)) {
                $value = $this->$value($key);

                if (is_integer($key) || ($value === null)) {
                    continue;
                }
            }
            $output[$key] = $value;
        }

        return $output;
    }

    /**
     * @param string $filename The name of the snippet
     * @param \MUtil\Ra::pairs $parameter_value_pairs name/value pairs ot add to the source for this snippet
     */
    public function addSnippet(string $filename, $parameter_value_pairs = null): void
    {
        $this->addSnippets([$filename], Ra::pairs(func_get_args(), 1));
    }

    /**
     * @param string[]|string $filenames Names of snippets
     * @param \MUtil\Ra::pairs $parameter_value_pairs name/value pairs ot add to the source for this snippet
     * @return mixed The snippet if content was possibly added.
     */
    public function addSnippets(mixed $filenames, $parameter_value_pairs = null): void
    {
        foreach ((array) $filenames as $filename) {
            $this->_snippetNames[] = $filename;
        }
        foreach (Ra::pairs(func_get_args(), 1) as $key => $value) {
            $this->_snippetParams[$key] = $value;
        }
    }
    
    /**
     * Set the action key in request
     *
     * Use this when an action is a Ajax action for retrieving
     * information for use within the screen of another action
     *
     * @param string $alias
     */
    protected function aliasAction($alias)
    {
        /**
         * TODO reimplement alias action
         */
        /*$request = $this->getRequest();
        $request->setActionName($alias);
        $request->setParam($request->getActionKey(), $alias);*/
    }

    /**
     * Apply this source to the target.
     *
     * @param \MUtil\Registry\TargetInterface $target
     * @return boolean True if $target is OK with loaded requests
     */
    public function applySource(\MUtil\Registry\TargetInterface $target)
    {
        return $this->snippetLoader->getOverLoader()->applyToLegacyTarget($target);
    }


    /**
     * The automatically filtered result
     *
     * @param $resetMvc bool When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        // \MUtil\Model::$verbose = true;

        // We do not need to return the layout, just the above table
        if ($resetMvc) {
            // Make sure all links are generated as if the current request was index.
            $this->aliasAction('index');

            \Zend_Layout::resetMvcInstance();
        }

        if ($this->autofilterSnippets) {
            $params = $this->_processParameters($this->autofilterParameters + $this->_defaultAutofilterParameters);

            $this->addSnippets($this->autofilterSnippets, $params);
        }

        if ($resetMvc) {
            // Lazy call here, because any echo calls in the snippets have not yet been
            // performed. so they will appear only in the next call when not lazy.
            $this->html->raw(\MUtil\Lazy::call(array('\\MUtil\\EchoOut\\EchoOut', 'out')));
        }
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction()
    {
        if ($this->createEditSnippets) {
            $params = $this->_processParameters($this->createParameters + $this->createEditParameters + $this->_defaultCreateParameters);

            $this->addSnippets($this->createEditSnippets, $params);
        }
    }

    public function createConsentModel($detailed, $action)
    {
        $model = new \MUtil\Model\TableModel('gems__consents');
        // $model->copyKeys(); // The user can edit the keys.
        $model->addColumn('gco_description', 'origKey');

        $model->set('gco_description', 'label', $this->_('Description'), 'size', '10', 'translate', true);

        $model->set('gco_order',       'label', $this->_('Order'), 'size', '10',
                    'description', $this->_('Determines order of presentation in interface.'),
                    'validator', 'Digits');
        $model->set('gco_code',        'label', $this->_('Consent code'),
                    'multiOptions', $this->util->getConsentTypes(),
                    'description', $this->_('Internal code, not visible to users, copied with the token information to the source.'));
        if ($detailed) {
            $model->set('gco_description', 'validator', $model->createUniqueValidator('gco_description'));
            $model->set('gco_order',       'validator', $model->createUniqueValidator('gco_order'));
        }

        if ($this->project->translateDatabaseFields()) {
            if ('create' == $action || 'edit' == $action) {
                $this->loader->getModels()->addDatabaseTranslationEditFields($model);
            } else {
                $this->loader->getModels()->addDatabaseTranslations($model);
                $model->setKeys(['origKey']);
            }
        }

        \Gems\Model::setChangeFieldsByPrefix($model, 'gco');

        return $model;
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
    protected function createModel($detailed, $action)
    {
        switch (strtolower($this->requestInfo->getCurrentController())) {
            case 'gems\actions\consentaction':
                return $this->createConsentModel($detailed, $action);
                
            default:
                return null;
        }
    }

    /**
     * Action for showing a deactivate item page
     */
    public function deactivateAction()
    {
        if ($this->deactivateSnippets) {
            $params = $this->_processParameters($this->deactivateParameters);

            $this->addSnippets($this->deactivateSnippets, $params);
        }
    }

    /**
     * Action for showing a delete item page
     */
    public function deleteAction()
    {
        if ($this->deleteSnippets) {
            $params = $this->_processParameters($this->deleteParameters);

            $this->addSnippets($this->deleteSnippets, $params);
        }
    }

    /**
     * Action for showing a edit item page
     */
    public function editAction()
    {
        if ($this->createEditSnippets) {
            $params = $this->_processParameters($this->editParameters + $this->createEditParameters + $this->_defaultEditParameters);

            $this->addSnippets($this->createEditSnippets, $params);
        }
    }

    /**
     *
     * @param string $action The current action.
     * @return boolean True when this actions uses a form
     */
    public function forForm($action)
    {
        return in_array($action, $this->formActions);
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns(): mixed
    {
        return false;
    }

    /**
     * Get the cache tags for this model (if any)
     *
     * @return array
     */
    public function getCacheTags()
    {
        return (array) $this->cacheTags;
    }

    /**
     * Name of the default import translator
     *
     * @return string
     */
    public function getDefaultImportTranslator()
    {
        return 'default';
    }

    /**
     * Get the possible translators for the import snippet.
     *
     * @return array of \MUtil\Model\ModelTranslatorInterface objects
     */
    public function getImportTranslators()
    {
        $trs = new \MUtil\Model\Translator\StraightTranslator($this->_('Direct import'));
        $this->applySource($trs);

        return array('default' => $trs);
    }

    /**
     *
     * @return boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     */
    public function getIncludeNumericFilters()
    {
        return $this->includeNumericFilters;
    }

    /**
     * Returns the model for the current $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function getModel()
    {
        $action = strtolower($this->requestInfo->getCurrentAction());

        // Only get new model if there is no model or the model was for a different action
        if (! ($this->_model && $this->_model->isMeta('action', $action))) {
            $detailed = ! $this->isSummarized($action);

            $container = \MUtil\Model::getSource()->getContainer();
            if ($container instanceof ServiceManager) {
                $container->setService('action', $action);
                $container->setService('detailed', $detailed);
                $container->setService('forForm', $this->forForm($action));
            }

            $this->_model = $this->createModel($detailed, $action);
            $this->_model->setMeta('action', $action);

            // Detailed models DO NOT USE $_POST for filtering,
            // multirow models DO USE $_POST parameters for filtering.
            $parameters = $this->request->getQueryParams();
            if (!$detailed) {
                $parameters += $this->request->getParsedBody();
            }

            // Remove all empty values (but not arrays)
            $parameters = array_filter($parameters, function($i) {
                return is_array($i) || strlen($i);
            });

            $this->_model->applyParameters($parameters, $this->includeNumericFilters);
        }

        return $this->_model;
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
    public function getSearchData($useRequest = true)
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

            if (isset($data[\MUtil\Model::AUTOSEARCH_RESET]) && $data[\MUtil\Model::AUTOSEARCH_RESET]) {
                // Clean up values
                $sessionData = [];

                //$request->setParam(\MUtil\Model::AUTOSEARCH_RESET, null);
            } else {
                $data = $data + $sessionData;
            }

            // Always remove
            unset($data[\MUtil\Model::AUTOSEARCH_RESET]);

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
        $this->_searchData = array_filter($data, function($i) { return is_array($i) || $i instanceof \DateTimeInterface || strlen($i); });

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
    public function getSearchDefaults()
    {
        return $this->defaultSearchData;
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        if (false !== $this->_searchFilter) {
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $this->requestInfo = RequestInfoFactory::getMezzioRequestInfo($request);
        
        $action   = $this->requestInfo->getCurrentAction() ?: 'index'; 
        $function = $action . 'Action';

//        file_put_contents('modelsnippet.txt', strtolower($this->requestInfo->getCurrentController()) . "\n", FILE_APPEND);
//        file_put_contents('modelsnippet.txt', $function . "\n", FILE_APPEND);
        
        $this->$function();

//        file_put_contents('modelsnippet.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . print_r($this->_snippetNames, true) . "\n", FILE_APPEND);
//        file_put_contents('modelsnippet.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . array_keys($this->_snippetParams), true) . "\n", FILE_APPEND);
        
        if ($this->html->count() || (! $this->_snippetNames)) {
            $this->_snippetNames[] = 'HtmlContentSnippet';
        }
        return $this->responder->getSnippetsResponse($this->_snippetNames, $this->_snippetParams, $request);
    }

    /**
     * Generic model based import action
     */
    public function importAction()
    {
        if ($this->importSnippets) {
            $params = $this->_processParameters($this->importParameters + $this->_defaultImportParameters);

            $this->addSnippets($this->importSnippets, $params);
        }
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        if ($this->indexStartSnippets || $this->indexStopSnippets) {
            $params = $this->_processParameters(
                $this->indexParameters + $this->autofilterParameters + $this->_defaultAutofilterParameters
            );

            if ($this->indexStartSnippets) {
                $this->addSnippets($this->indexStartSnippets, $params);
            }
        }

        $this->autofilterAction(false);

        if ($this->indexStopSnippets) {
            $this->addSnippets($this->indexStopSnippets, $params);
        }
    }

    /**
     *
     * @param string $action The current action.
     * @return boolean True when this actions uses only summary data
     */
    public function isSummarized($action)
    {
        return in_array($action, $this->summarizedActions);
    }

    /**
     * Action for showing a reactivate item page
     */
    public function reactivateAction()
    {
        if ($this->reactivateSnippets) {
            $params = $this->_processParameters($this->reactivateParameters);

            $this->addSnippets($this->reactivateSnippets, $params);
        }
    }

    /**
     * Action for showing an item page
     */
    public function showAction()
    {
        if ($this->showSnippets) {
            $params = $this->_processParameters($this->showParameters);

            $this->addSnippets($this->showSnippets, $params);
        }
    }
}