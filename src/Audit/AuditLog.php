<?php

namespace Gems\Audit;

use Exception;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Db\CachedResultFetcher;
use Gems\Exception\Coding;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Repository\RespondentRepository;
use Gems\User\User;
use Gems\User\UserLoader;
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

    protected ?int $lastLogId = null;

    /**
     * @var array|string[] routes that should always be logged as request, those not containing \\. will have that added
     */
    protected array $logRequestActions = [
        'answer', 'ask\\.forward', 'ask\\.return', 'ask\\.take', 'logout', 'respondent.*\\.show', 'to-survey',
        ];

    /**
     * @var array|string[] routes that should always be logged when changed, those not containing \\. will have that added
     */
    protected array $logRequestChanges = [
        'active-toggle', 'answer-export', 'ask\\.lost', 'attributes', 'cacheclean', 'change', 'check', 'cleanup',
        'correct', 'create', 'delete', 'download', 'edit', 'execute', 'export', 'import', 'insert', 'lock',
        'maintenance-mode', 'merge', 'patches', 'ping', 'recalc', 'reset', 'run', 'seeds', 'subscribe',
        'synchronize', 'two-factor', 'undelete', 'unsubscribe',
        ];

    protected ServerRequestInterface $request;

    protected array $respondentIdFields = [
        'grs_id_user', 'gr2o_id_user', 'gr2t_id_user', 'gap_id_user', 'gec_id_user', 'gto_id_respondent', 'grr_id_respondent'
    ];

    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected RespondentRepository $respondentRepository,
    )
    {}

    /**
     * Get a specific action from the database actions
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
        if (isset($actions[$routeName])) {
            return $actions[$routeName];
        }

        $logChange  = 0;
        $logRequest = 0;
        foreach ($this->logRequestActions as $routePart) {
            if ($this->matchAction($routeName, $routePart)) {
                $logRequest = 1;
                break;
            }
        }
        foreach ($this->logRequestChanges as $routePart) {
            if ($this->matchAction($routeName, $routePart)) {
                $logChange = 1;
                break;
            }
        }

        $logAction = [
            'gls_name' => $routeName,
            'gls_when_no_user' => 0,
            'gls_on_action' => $logRequest,
            'gls_on_post' => 0,
            'gls_on_change' => $logChange,
            'gls_changed' => new Expression('NOW()'),
            'gls_changed_by' => 0,
            'gls_created' => new Expression('NOW()'),
            'gls_created_by' => 0,
        ];

        $table = new TableGateway('gems__log_setup', $this->cachedResultFetcher->getAdapter());
        $table->insert($logAction);

        $actions = $this->getDbActions(true);
        return $actions[$routeName];
    }

    protected function getCurrentOrganizationId(): int
    {
        $organizationId = $this->request->getAttribute(CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE);
        if ($organizationId !== null) {
            return $organizationId;
        }
        $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($currentUser instanceof User) {
            return $currentUser->getCurrentOrganizationId();
        }

        return 0;
    }

    protected function getCurrentRole(): string
    {
        $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($currentUser instanceof User) {
            return $currentUser->getRole();
        }

        return 'nologin';
    }


    protected function getCurrentUserId(): int
    {
        $currentUser = $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($currentUser instanceof User) {
            return $currentUser->getUserId();
        }

        return $this->request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE, UserLoader::UNKNOWN_USER_ID);
    }

    public function getDbActions(bool $refresh = false): array
    {
        if ($refresh) {
            // Delete cache value
            $this->cachedResultFetcher->getCache()->deleteItem($this->actionsCacheKey);
        }
        $select = $this->cachedResultFetcher->getSelect('gems__log_setup');
        $select->order(['gls_name']);

        $actions = $this->cachedResultFetcher->fetchAll($this->actionsCacheKey, $select);
        if ($actions) {
            return array_combine(array_column($actions, 'gls_name'), $actions);
        }
        return [];
    }

    /**
     * Get the user IP address
     *
     * @return string|null
     */
    public function getIp(): string|null
    {
        $params = $this->request->getServerParams();
        if (isset($params['REMOTE_ADDR'])) {
            return $params['REMOTE_ADDR'];
        }
        return null;
    }

    /**
     * @return int|null
    */
    public function getLastLogId(): ?int
    {
        return $this->lastLogId ?? 0;
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

        $id1 = $this->request->getAttribute(MetaModelInterface::REQUEST_ID1);
        $id2 = $this->request->getAttribute(MetaModelInterface::REQUEST_ID2);

        if ($id1 !== null && $id2 !== null) {
            return $this->respondentRepository->getRespondentId($id1, $id2);
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
//        $this->lastLogId = $this->storeLogEntry($actionData['gls_id_action'], $message, $data, $this->getRespondentId($data), false);
//
//        return $this->lastLogId;
//    }

    protected function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    protected function matchAction($route, $action): bool
    {
        if (str_contains($action, '\\.')) {
            $pattern = '/^' . $action . '[^.]*$/';
        } else {
            $pattern = '/\\.' . $action . '[^.]*$/';
        }
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

        $this->lastLogId = $this->storeLogEntry($actionData['gls_id_action'], $messages, $data, true, $respondentId, $logId);

        return $this->lastLogId;
    }

    public function registerRequest(ServerRequestInterface $request):? int
    {
        $this->setRequest($request);
        $actionData = $this->getAction();

        if (!$this->shouldLogAction($actionData)) {
            return null;
        }

        $data         = $this->getRequestData();
        $message      = $this->getRequestMessages();
        $respondentId = $this->getRespondentId($data);

        $this->lastLogId = $this->storeLogEntry($actionData['gls_id_action'], $message, $data, false, $respondentId, 0);

        return $this->lastLogId;
    }

    public function shouldLogAction(array $actionData, bool $change = false): bool
    {
        if ($change) {
            $checkField = 'gls_on_change';
        } else {
            $checkField = 'gls_on_action';
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

        if ($actionData[$checkField] != 1) {
            return false;
        }
        return true;
    }

    protected function storeLogEntry($route, $message, $data, bool $changed, ?int $respondentId, int $logId = 0): ? int
    {
        $logData = [
            'gla_action'        => $route,
            'gla_method'        => $this->request->getMethod(),
            'gla_by'            => $this->getCurrentUserId(),
            'gla_changed'       => (int) ($changed || $this->isChanged($this->request)),
            'gla_message'       => json_encode($message),
            'gla_data'          => json_encode($data),
            'gla_remote_ip'     => $this->getIp(),
            'gla_respondent_id' => $respondentId,
            'gla_organization'  => $this->getCurrentOrganizationId(),
            'gla_role'          => $this->getCurrentRole(),
        ];

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
            return $logId;
        }
    }
}