<?php

namespace Gems\Audit;

use Exception;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Db\CachedResultFetcher;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Repository\RespondentRepository;
use Gems\User\User;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Mezzio\Router\RouteResult;
use MUtil\Model;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Message\StatusMessengerInterface;

class AuditLog
{
    protected string $actionsCacheKey = 'logActions';
    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected RespondentRepository $respondentRepository,
    )
    {}

    /**
     * Get a specific action from the database actions
     *
     * @param string $action
     * @return array
     */
    public function getAction(string $action): array
    {
        $action = strtolower($action);
        $actions = $this->getDbActions();
        if (isset($actions[$action])) {
            return $actions[$action];
        }

        $logAction = [
            'gls_name' => $action,
            'gls_when_no_user' => 0,
            'gls_on_action' => 0,
            'gls_on_post' => 0,
            'gls_on_change' => 0,
            'gls_changed' => new Expression('NOW()'),
            'gls_changed_by' => 0,
            'gls_created' => new Expression('NOW()'),
            'gls_created_by' => 0,
        ];

        $table = new TableGateway('gems__log_setup', $this->cachedResultFetcher->getAdapter());
        $table->insert($logAction);

        $actions = $this->getDbActions(true);
        return $actions[$action];
    }

    protected function getCurrentOrganizationFromRequest(ServerRequestInterface $request): int
    {
        $organizationId = $request->getAttribute(CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE);
        if ($organizationId !== null) {
            return $organizationId;
        }
        $currentUser = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($currentUser instanceof User) {
            return $currentUser->getCurrentOrganizationId();
        }

        return 0;
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
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function getIp(ServerRequestInterface $request): string|null
    {
        $params = $request->getServerParams();
        if (isset($params['REMOTE_ADDR'])) {
            return $params['REMOTE_ADDR'];
        }
        return null;
    }

    /**
     * Get the data of the request
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getRequestData(ServerRequestInterface $request): array
    {
        $method = $request->getMethod();
        switch($method) {
            case 'GET':
            case 'DELETE':
                $data = $request->getQueryParams();
                break;
            case 'PATCH':
            case 'POST':
                $data = $request->getParsedBody();
                break;
            default:
                $data = [];
        }

        return $data;
    }

    /**
     * Get the message from a request
     *
     * @param ServerRequestInterface $request
     * @return null
     */
    public function getRequestMessage(ServerRequestInterface $request)
    {
        /**
         * @var $messenger StatusMessengerInterface
         */
        $messenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        return $messenger->getMessages(null, true);
    }

    public function getRequestRespondentId(ServerRequestInterface $request): ?int
    {
        $id1 = $request->getAttribute(Model::REQUEST_ID1);
        $id2 = $request->getAttribute(Model::REQUEST_ID2);

        if ($id1 !== null && $id2 !== null) {
            return $this->respondentRepository->getRespondentId($id1, $id2);
        }
        return null;
    }

    /**
     * Get the current Action from the route
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function getRouteName(ServerRequestInterface $request): string
    {
        $routeResult = $request->getAttribute(RouteResult::class);
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

    public function logChange(ServerRequestInterface $request, mixed $message = null, mixed $data = null, ?int $respondentId = null): array|null
    {
        return $this->logRequest($request, $message, $data, $respondentId, true);
    }

    public function logRequest(ServerRequestInterface $request, mixed $message = null, mixed $data = null, ?int $respondentId=null, bool $changed = false): array|null
    {
        $routeName = $this->getRouteName($request);
        $actionData = $this->getAction($routeName);

        if (!$this->shouldLogAction($actionData, $request->getMethod())) {
            return null;
        }

        $by = 0;
        $role = '';
        $currentUser = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($currentUser instanceof User) {
            $by = $currentUser->getUserId();
            $role = $currentUser->getRole();
        }

        if ($data === null) {
            $data = $this->getRequestData($request);
        }
        $ip = $this->getIp($request);
        if ($message === null) {
            $message = $this->getRequestMessage($request);
        }
        if ($respondentId === null) {
            $respondentId = $this->getRequestRespondentId($request);
        }

        $log = [
            'gla_action' => $actionData['gls_id_action'],
            'gla_method' => $request->getMethod(),
            'gla_by' => $by,
            'gla_changed' => (int) ($changed || $this->isChanged($request)),
            'gla_message' => json_encode($message),
            'gla_data' => json_encode($data),
            'gla_remote_ip' => $ip,
            'gla_respondent_id' => $respondentId,
            'gla_organization' => $this->getCurrentOrganizationFromRequest($request),
            'gla_role' => $role,
        ];

        return $this->storeLogEntry($log);
    }

    public function shouldLogAction(array $actionData, string $method): bool
    {
        $checkField = 'gls_on_action';
        switch($method) {
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

        if ($actionData[$checkField] != 1) {
            return false;
        }
        return true;
    }

    protected function storeLogEntry(array $logData, $new = true): ?array
    {
        $sql = new Sql($this->cachedResultFetcher->getAdapter());
        if ($new) {
            $insert = $sql->insert();
            $insert->into('gems__log_activity')
                ->columns(array_keys($logData))
                ->values($logData);

            try {
                $statement = $sql->prepareStatementForSqlObject($insert);
                $result = $statement->execute();
                if ($id = $result->getGeneratedValue()) {
                    $log['gla_id'] = $id;
                }
                return $log;
            } catch (Exception $e) {
                return null;
            }
        }

        $update = $sql->update();
        $update->table('gems__log_activity')
            ->set($logData)
            ->where(['gla_id' => $logData['gla_id']]);
        try {
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
            return $logData;
        } catch (Exception $e) {
            return null;
        }
    }
}