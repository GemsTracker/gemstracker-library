<?php

namespace Gems\Audit;

use Exception;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Db\CachedResultFetcher;
use Gems\Exception\Coding;
use Gems\Middleware\ClientIpMiddleware;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\RespondentRepository;
use Gems\User\User;
use Gems\User\UserLoader;
use Gems\Versions;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;

class AuditLog
{
    protected string $actionsCacheKey = 'logActions';

    protected array $actionsCacheTags = ['accesslog_actions', 'logActions'];

    protected ?int $lastLogId = null;

    /**
     * Array of data to save to the gems__log_activity table.
     */
    protected array $logData = [];

    /**
     * @var string[] Attributes for which defaults are taken from config during creation.
     */
    protected array $overridableAttributes = [
        'when_no_user',
        'on_action',
        'on_post',
        'on_change',
    ];

    protected array $organizationIdFields = [
        'gr2o_id_organization', 'gr2t_id_organization', 'gap_id_organization', 'gec_id_organization', 'gto_id_organization', 'gor_id_organization',
        'gor_id_organization', 'gul_id_organization'
    ];

    protected ServerRequestInterface $request;

    protected array $respondentIdFields = [
        'grs_id_user', 'gr2o_id_user', 'gr2t_id_user', 'gap_id_user', 'gec_id_user', 'gto_id_respondent', 'grr_id_respondent'
    ];

    protected ?User $user = null;

    protected array $userAttributes = [
        AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE,
        AuthenticationMiddleware::CURRENT_USER_WITHOUT_TFA_ATTRIBUTE
    ];

    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected RespondentRepository $respondentRepository,
        protected readonly Versions $versions,
        protected readonly array $config,
    )
    {}

    /**
     * Get a specific action from the database actions. An action is inserted
     * into the database if it doesn't exist yet, and updated if it does exist
     * but isn't uptodate.
     *
     * @return array
     * @throws Coding
     */
    public function getAction(string|null $routeName = null): array
    {
        if ($routeName === null) {
            $routeName = strtolower($this->getRouteName());
        }
        $actions = $this->getDbActions();
        if (isset($actions[$routeName]) && $this->isUptodate($actions[$routeName])) {
            return $actions[$routeName];
        }

        if (isset($actions[$routeName])) {
            $logAction = $actions[$routeName];
            // These values don't need to be updated.
            unset($logAction['gls_name']);
            unset($logAction['gls_created']);
            unset($logAction['gls_created_by']);
            // But these are.
            $logAction['gls_changed'] = new Expression('NOW()');
            $logAction['gls_changed_by'] = 0;
        } else {
            $logAction = [
                'gls_name' => $routeName,
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 0,
                'gls_changed' => new Expression('NOW()'),
                'gls_changed_by' => 0,
                'gls_created' => new Expression('NOW()'),
                'gls_created_by' => 0,
            ];
        }
        $logAction = array_merge($logAction, $this->getOverrideAttributes($routeName));

        // As long as the related migration hasn't been executed, we cannot
        // add the application version to the record.
        if (isset($actions[$routeName]) && !array_key_exists('gls_app_version', $actions[$routeName])) {
            unset($logAction['gls_app_version']);
        }

        $table = new TableGateway('gems__log_setup', $this->cachedResultFetcher->getAdapter());
        if (isset($actions[$routeName])) {
            $id = $logAction['gls_id_action'];
            unset($logAction['gls_id_action']);
            $table->update($logAction, ['gls_id_action' => $id]);
        } else {
            $table->insert($logAction);
        }

        $this->cachedResultFetcher->invalidateTags($this->actionsCacheTags);

        $actions = $this->getDbActions(true);
        return $actions[$routeName];
    }

    /**
     * Return true if the configured action is based on the config of the current
     * application version, or false if it isn't.
     */
    protected function isUptodate(array $action): bool
    {
        if (!isset($action['gls_app_version'])) {
            return false;
        }
        return $action['gls_app_version'] === $this->versions->getProjectVersion();
    }

    /**
     * Check the configuration to see if we need to override specific settings
     * for this route.
     */
    protected function getOverrideAttributes(string $routeName): array
    {
        $overrideAttributes = [];
        foreach ($this->overridableAttributes as $config_key) {
            if (!isset($this->config['auditlog'][$config_key])) {
                // Config key not set, nothing to override.
                continue;
            }
            $routeParts = $this->config['auditlog'][$config_key];
            $attribute = 'gls_' . $config_key;
            foreach ($routeParts as $routePart) {
                if ($this->matchAction($routeName, $routePart)) {
                    $overrideAttributes[$attribute] = 1;
                    break;
                }
            }
        }
        // Set the application version.
        $overrideAttributes['gls_app_version'] = $this->versions->getProjectVersion();

        return $overrideAttributes;
    }

    protected function getCurrentOrganizationId(): int
    {
        $this->getCurrentUser();
        if ($this->user) {
            return $this->user->getCurrentOrganizationId();
        }
        if (isset($this->request)) {
            $organizationId = $this->request->getAttribute(CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE);
            if ($organizationId !== null) {
                return $organizationId;
            }
            $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
            if ($currentUser instanceof User) {
                return $currentUser->getCurrentOrganizationId();
            }
        }

        return 0;
    }

    protected function getCurrentRole(): string
    {
        $currentUser = $this->getCurrentUser();
        if ($currentUser instanceof User) {
            return $currentUser->getRole();
        }

        return 'nologin';
    }

    protected function getCurrentUser(): ?User
    {
        if (null === $this->user && isset($this->request)) {
            foreach ($this->userAttributes as $attr) {
                $current = $this->request->getAttribute($attr);
                if ($current instanceof User) {
                    $this->user = $current;
                    break;
                }
            }
        }

        return $this->user;
    }

    protected function getCurrentUserId(): int
    {
        $currentUser = $this->getCurrentUser();
        if ($currentUser instanceof User) {
            return $currentUser->getUserId();
        }

        if (isset($this->request)) {
            return $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE, UserLoader::UNKNOWN_USER_ID);
        }

        return UserLoader::UNKNOWN_USER_ID;
    }

    public function getDbActions(bool $refresh = false): array
    {
        if ($refresh) {
            // Delete cache value
            $this->cachedResultFetcher->getCache()->deleteItem($this->actionsCacheKey);
        }
        $select = $this->cachedResultFetcher->getSelect('gems__log_setup');
        $select->order(['gls_name']);

        $actions = $this->cachedResultFetcher->fetchAll($this->actionsCacheKey, $select, null, $this->actionsCacheTags);
        if ($actions) {
            return array_combine(array_column($actions, 'gls_name'), $actions);
        }
        return [];
    }

    /**
     * @return int|null
    */
    public function getLastLogId(): ?int
    {
        return $this->lastLogId ?? 0;
    }

    protected function getOrganizationId(array $data = [])
    {
        if ($data) {
            foreach ($this->organizationIdFields as $field) {
                if (isset($data[$field]) && $data[$field]) {
                    return $data[$field];
                }
            }
        }

        return $this->getCurrentOrganizationId();
    }

    /**
     * Get the data of the request
     *
     * @return array
     */
    public function getRequestData(): array
    {
        $data = $this->getRouteData();

        $data = $this->request->getQueryParams() + $data;
        switch($this->request->getMethod()) {
            case 'PATCH':
            case 'POST':
                $data = $this->request->getParsedBody() + $data;
                break;
        }
        // Obfuscate passwords / tfa's
        foreach ($data as $name => $value) {
            if ($value instanceof \DateTimeInterface) {
                $data[$name] = $value->format('c');
            }
            if (is_object($value)) {
                $data[$name] = get_class($value);
            }
            foreach (['password', 'pwd', 'tfa'] as $check) {
                if (str_contains($name, $check)) {
                    $data[$name] = '******';
                }
            }
            foreach (['csrf'] as $check) {
                if (str_contains($name, $check)) {
                    unset($data[$name]);
                }
            }
        }

        return $data;
    }

    /**
     * Get the message from a request
     *
     * @return array
     */
    public function getRequestMessages(): array
    {
        /**
         * @var StatusMessengerInterface $messenger
         */
        $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        return Ra::flatten($messenger->getMessages(null, true));
    }

    public function getRespondentId(array $data): ?int
    {
        if ($data) {
            foreach ($this->respondentIdFields as $field) {
                if (isset($data[$field]) && $data[$field]) {
                    return $data[$field];
                }
            }
        }

        $this->getCurrentUser();
        if (isset($this->request)) {
            $patientNr = $this->request->getAttribute(MetaModelInterface::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(MetaModelInterface::REQUEST_ID2);
            if ($this->user && $organizationId !== null) {
                $this->user->assertAccessToOrganizationId($organizationId);
            }

            if ($patientNr !== null && $organizationId !== null) {
                return $this->respondentRepository->getRespondentId($patientNr, $organizationId);
            }
        }
        return null;
    }

    public function getRouteData(): array
    {
        if (! isset($this->request)) {
            throw new Coding("Asking for route before the request was set");
        }

        $routeResult = $this->request->getAttribute(RouteResult::class);
        if ($routeResult instanceof RouteResult) {
            return $routeResult->getMatchedParams();
        }
        return [];
    }

    /**
     * Get the current Action from the route
     *
     * @return string
     * @throws Coding
     */
    public function getRouteName(): string
    {
        if (! isset($this->request)) {
            throw new Coding("Asking for route before the request was set");
        }
        $routeResult = $this->request->getAttribute(RouteResult::class);
        $route = $routeResult->getMatchedRoute();

        return $route->getName();
    }

    protected function isChanged(ServerRequestInterface $request): bool
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }
        return false;
    }

    /**
     * @param ServerRequestInterface $request
     * @param mixed|null $message
     * @param array $data
     * @param int|null $respondentId
     * @return int|null
     * @deprecated Use the register functions!
     */
    public function logChange(ServerRequestInterface $request, mixed $message = null, array $data = [], ?int $respondentId = null): ? int
    {
        if (null === $respondentId) {
            $respondentId = 0;
        }
        $logId = $this->registerRequest($request);
        if (null == $logId) {
            $logId = 0;
        }
        $this->registerChanges($data, [], (array) $message, $respondentId, $logId);
        return $logId;
    }

//    public function logRequest(ServerRequestInterface $request, mixed $message = null, mixed $data = null):? int
//    {
//        $this->setRequest($request);
//        $actionData = $this->getAction();
//
//        if (!$this->shouldLogAction($actionData)) {
//            return null;
//        }
//
//        if ($data === null) {
//            $data = $this->getRequestData($request);
//        }
//        if ($message === null) {
//            $message = $this->getRequestMessage($request);
//        }
//
//        $this->lastLogId = $this->storeRequestEntry($actionData['gls_id_action'], $message, $data, $this->getRespondentId($data), false);
//
//        return $this->lastLogId;
//    }

    protected function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    protected function setUser(User $user)
    {
        $this->user = $user;
    }

    protected function matchAction(string $route, string $action): bool
    {
        $pattern = '/(^|\\.)' . preg_quote($action) . '(\\.|$)/';
        return (bool) preg_match($pattern, $route);
    }

    public function registerChanges(array $currentValues, array $oldValues = [], array $messages = [], int $respondentId = 0, int $logId = 0):? int
    {
        $actionData = $this->getAction();

        if (!$this->shouldLogAction($actionData, true)) {
            return null;
        }

        $data = $this->getRouteData() + array_diff_assoc($currentValues, $oldValues);
        if (! $messages) {
            $messages = $this->getRequestMessages();
        }
        if (! $respondentId) {
            $respondentId = $this->getRespondentId($currentValues + $oldValues);
        }

        $this->lastLogId = $this->storeRequestEntry($actionData['gls_id_action'], $messages, $data, true, $respondentId, $logId);

        return $this->lastLogId;
    }

    public function registerCliChanges(string $routeName, array $currentValues, array $oldValues = [], array $messages = [], bool $changed = false, int $respondentId = 0, int $logId = 0):? int
    {
        $actionData = $this->getAction($routeName);

        if (!$this->shouldLogAction($actionData, $changed)) {
            return null;
        }

        $data = array_diff_assoc($currentValues, $oldValues);
        if (! $respondentId) {
            $respondentId = $this->getRespondentId($currentValues + $oldValues);
        }

        $this->logData = [
            'gla_action'        => $actionData['gls_id_action'],
            'gla_method'        => 'CLI',
            'gla_by'            => $this->getCurrentUserId(),
            'gla_changed'       => $changed ? 1 : 0,
            'gla_message'       => json_encode($messages),
            'gla_data'          => json_encode($data),
            'gla_remote_ip'     => 'n/a',
            'gla_respondent_id' => $respondentId,
            'gla_organization'  => $this->getOrganizationId($currentValues) ?? $this->getOrganizationId($oldValues),
            'gla_role'          => $this->getCurrentRole(),
        ];
        if (isset($this->request)) {
            $this->logData['gla_method']    = $this->request->getMethod();
            $this->logData['gla_remote_ip'] = $this->request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE);
//        } else {
//            print_r($this->logData);
        }

        $this->lastLogId = $this->storeLogEntry($logId);

        return $this->lastLogId;
    }

    public function registerRequest(ServerRequestInterface $request, array $messages = [], bool $changed = false):? int
    {
        $this->setRequest($request);
        $actionData = $this->getAction();

        if (!$this->shouldLogAction($actionData, $changed)) {
            return null;
        }

        $data         = $this->getRequestData();
        $message      = $messages + $this->getRequestMessages();
        $respondentId = $this->getRespondentId($data);

        $this->lastLogId = $this->storeRequestEntry($actionData['gls_id_action'], $message, $data, $changed, $respondentId, 0);

        return $this->lastLogId;
    }

    public function registerUserRequest(ServerRequestInterface $request, User $user, array $messages = []):? int
    {
        $this->setUser($user);
        return $this->registerRequest($request, $messages, true);
    }

    public function shouldLogAction(array $actionData, bool $change = false): bool
    {
        if ($change) {
            $checkField = 'gls_on_change';
        } else {
            $checkField = 'gls_on_action';
            if (isset($this->request)) {
                switch ($this->request->getMethod()) {
                    case 'OPTIONS':
                        return false;

                    case 'POST':
                    case 'PATCH':
                    case 'DELETE':
                        $checkField = 'gls_on_post';
                        break;
                    case 'GET':
                    default:
                        break;
                }
            }
        }

        if ($actionData[$checkField] != 1) {
            return false;
        }
        return true;
    }

    protected function storeLogEntry(int $logId = 0): ? int
    {
        $logData = $this->logData;
        $this->logData = [];
//        print_r($logData);
        $sql = new Sql($this->cachedResultFetcher->getAdapter());
        if (0 == $logId) {
            $insert = $sql->insert();
            $insert->into('gems__log_activity')
                ->columns(array_keys($logData))
                ->values($logData);

            try {
                $statement = $sql->prepareStatementForSqlObject($insert);
                $result = $statement->execute();
                if ($id = $result->getGeneratedValue()) {
                    return $id;
                }
            } catch (Exception $e) {
//                echo $e->getMessage();
                // error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS. 10), true));
                error_log(__CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  $e->getMessage());
                // error_log(print_r($logData, true));
            }
            return null;
        }

        $update = $sql->update();
        $update->table('gems__log_activity')
            ->set($logData)
            ->where(['gla_id' => $logId]);
        try {
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
            return $logId;
        } catch (Exception $e) {
//            echo $e->getMessage();
            error_log(__CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  $e->getMessage());
            // error_log(print_r($logData, true));
            return $logId;
        }
    }

    protected function storeRequestEntry($route, $message, $data, bool $changed, ?int $respondentId, int $logId = 0): ? int
    {
        $currentOrganizationId = $this->getCurrentOrganizationId();
        if ($currentOrganizationId === OrganizationRepository::SYSTEM_NO_ORG) {
            $currentOrganizationId = 0;
        }

        $this->logData = [
            'gla_action'        => $route,
            'gla_method'        => $this->request->getMethod(),
            'gla_by'            => $this->getCurrentUserId(),
            'gla_changed'       => (int) ($changed || $this->isChanged($this->request)),
            'gla_message'       => json_encode($message),
            'gla_data'          => json_encode($data),
            'gla_remote_ip'     => $this->request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE),
            'gla_respondent_id' => $respondentId,
            'gla_organization'  => $currentOrganizationId,
            'gla_role'          => $this->getCurrentRole(),
        ];

        return $this->storeLogEntry($logId);
    }
}
