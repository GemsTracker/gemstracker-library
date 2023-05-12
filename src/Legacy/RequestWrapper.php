<?php

declare(strict_types=1);

namespace Gems\Legacy;

use Psr\Http\Message\ServerRequestInterface;

class RequestWrapper
{
    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * @var ?mixed[] route options
     */
    protected array $routeOptions = [];

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /*public function getClientIp(): ?string
    {
        $server = $this->request->getServerParams();
        if (isset($server['REMOTE_ADDR'])) {
            return $server['REMOTE_ADDR'];
        }

        return null;
    }*/

    /**
     * @return string[]
     */
    public function getParams(): array
    {
        $params = [
            'controller' => $this->getControllerName(),
            'action' => $this->getActionName(),
            'module' => 'default',
        ];

        $params += $this->request->getQueryParams();
        $params += $this->request->getParsedBody();

        return $params;
    }

    public function getParam($name)
    {
        $params = $this->getParams();
        if (isset($params[$name])) {
            return $params[$name];
        }
        return null;
    }


    public function getActionName()
    {
        $action = $this->request->getAttribute('action');
        if ($action === null) {
            return 'index';
        }

        return $action;
    }

    public function getActionKey()
    {
        return 'action';
    }

    public function getControllerKey()
    {
        return 'controller';
    }

    public function getControllerName()
    {
        $options = $this->getRouteOptions();
        if (isset($options['controller'])) {
            return $options['controller'];
        }
        return null;
    }

    public function getModuleKey()
    {
        return 'module';
    }

    public function getModuleName()
    {
        return 'module';
    }

    public function getRoute()
    {
        $routeResult = $this->getRouteResult();
        if (is_null($routeResult)) {
            return false;
        }
        return $routeResult->getMatchedRoute();
    }

    public function getRouteResult()
    {
        return $this->request->getAttribute('Zend\Expressive\Router\RouteResult');
    }

    protected function getRouteOptions()
    {
        if (!$this->routeOptions) {
            $route = $this->getRoute();
            if (!$route) {
                return null;
            }
            $this->routeOptions = $route->getOptions();
        }

        return $this->routeOptions;
    }

    public function isPost()
    {
        $method = $this->request->getMethod();
        if ($method == 'POST') {
            return true;
        }
        return false;
    }

    public function setParam($param, $value)
    {
        $this->request = $this->request->withQueryParams([$param => $value]);
    }
}
