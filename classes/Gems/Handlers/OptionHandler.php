<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers;

use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\LogModel;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class OptionHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['staff'];

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
    protected array $createEditParameters = [
        'menuShowChildren' => true,
        'onlyUsedElements' => true,
        'afterSaveRoutePart' => 'edit',
        'currentUser'      => 'getCurrentUser',
        'request' => 'getRequest',
    ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = ['User\\OwnAccountEditSnippet'];

    /**
     * The parameters used for the edit authentication action.
     */
    protected array $editAuthParameters = [
        'menuShowChildren' => true,
        'onlyUsedElements' => true,
        'afterSaveRoutePart' => 'edit-auth',
        'currentUser'      => 'getCurrentUser',
        'request' => 'getRequest',
    ];

    /**
     * The snippets used for the edit authentication action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $editAuthSnippets = ['User\\OwnAccountEditAuthSnippet'];

    /**
     * The parameters used for the reset action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $overviewParameters = [
        'browse'          => true,
        'contentTitle'    => 'getShowLogOverviewTitle',
        'explanationText' => 'getShowLogOverviewExplanation',
        'extraFilter'     => 'getShowLogOverviewFilter',
        'menuEditRoutes' => [],
        'menuShowRoutes' => ['show-log'],
        'model' => 'getLogModel',
    ];

    /**
     * Snippets for reset
     *
     * @var mixed String or array of snippets name
     */
    protected array $overviewSnippets = [
        'Generic\\ContentTitleSnippet',
        'Generic\\TextExplanationSnippet',
        'Log\\LogTableSnippet',
        'Generic\\CurrentSiblingsButtonRowSnippet',
    ];

    /**
     * The parameters used for the showLog action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $showLogParameters = [
        'contentTitle' => 'getShowLogItemTitle',
        'extraFilter'     => 'getShowLogOverviewFilter',
        'model' => 'getLogModel',
    ];

    /**
     * Snippets for showLog
     *
     * @var mixed String or array of snippets name
     */
    protected array $showLogSnippets = [
        'Generic\\ContentTitleSnippet',
        'Log\\LogShowSnippet',
        'Generic\\CurrentButtonRowSnippet',
    ];

    /**
     * The parameters used for the twoFactor action.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $twoFactorParameters = [
        'contentTitle' => 'getShowTwoFactorTitle',
        'routeAction'  => 'edit',
        'user'         => 'getCurrentUser',
    ];

    /**
     * Snippets for twoFactor action
     *
     * @var mixed String or array of snippets name
     */
    protected array $twoFactorSnippets = [
        'Generic\\ContentTitleSnippet',
        'User\\SetTwoFactorSnippet',
        'Generic\\CurrentButtonRowSnippet',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        private readonly array $config,
        private readonly CurrentUserRepository $currentUserRepository,
        private readonly Model $modelContainer,
    ) {
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
    public function createModel(bool $detailed, string $action): DataReaderInterface
    {
        $model = $this->modelContainer->getStaffModel(false);

        if ($action !== 'edit-auth') {
            $model->applyOwnAccountEdit(!$this->config['account']['edit-auth']['enabled']);
        }

        return $model;
    }

    /**
     *
     * @return \Gems\User\User
     */
    public function getCurrentUser()
    {
        return $this->currentUserRepository->getCurrentUser();
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return $string
     */
    public function getEditTitle(): string
    {
        return $this->_('Options');
    }

    protected function getLogModel(): LogModel
    {
        $model = $this->modelContainer->createLogModel();
        $action = $this->requestInfo->getCurrentAction();
        if ($action === 'overview') {
            $model->applyBrowseSettings();
        }
        if ($action === 'show-log') {
            $model->applyDetailSettings();
        }

        return $model;
    }

    /**
     *
     * @return string Title for show log item
     */
    public function getShowLogItemTitle()
    {
        return $this->_('Show activity');
    }

    /**
     *
     * @return string Explanation for show log overview
     */
    public function getShowLogOverviewExplanation()
    {
        return $this->_('This overview provides information about the last login activity on your account.');
    }

    /**
     * Get a filter for the show log snippet
     */
    public function getShowLogOverviewFilter()
    {
        return ['gla_by' => $this->getCurrentUser()->getUserId()];
    }

    /**
     *
     * @return string Title for show log overview
     */
    public function getShowLogOverviewTitle()
    {
        return $this->_('Activity overview');
    }

    /**
     *
     * @return string Title for twoFactor overview
     */
    public function getShowTwoFactorTitle()
    {
        return $this->_('Set a new two factor code');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('your setup', 'your setup', $count);
    }

    /**
     * Action for showing the edit authentication page
     */
    public function editAuthAction()
    {
        if ($this->editAuthSnippets) {
            $params = $this->_processParameters($this->editAuthParameters);

            $params['session'] = $this->request->getAttribute(SessionInterface::class);

            $this->addSnippets($this->editAuthSnippets, $params);
        }
    }

    /**
     * Show log overview for the current user
     */
    public function overviewAction()
    {
        if ($this->overviewSnippets) {
            $params = $this->_processParameters($this->overviewParameters);

            $this->addSnippets($this->overviewSnippets, $params);
        }
    }

    /**
     * Show a log item
     */
    public function showLogAction()
    {
        if ($this->showLogSnippets) {
            $params = $this->_processParameters($this->showLogParameters);
            $this->addSnippets($this->showLogSnippets, $params);
        }
    }

    /**
     * Set two factor authentication
     */
    public function twoFactorAction()
    {
        if ($this->twoFactorSnippets) {
            $params = $this->_processParameters($this->twoFactorParameters);

            $params['session'] = $this->request->getAttribute(SessionInterface::class);

            $this->addSnippets($this->twoFactorSnippets, $params);
        }
    }
}
