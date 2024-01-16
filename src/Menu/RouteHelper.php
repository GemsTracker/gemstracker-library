<?php

namespace Gems\Menu;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\User\User;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Exception\RuntimeException;
use Zalt\Late\Late;
use Zalt\Late\LateCall;
use Zalt\Late\LateInterface;
use Zalt\Model\Bridge\BridgeInterface;

class RouteHelper
{
    private bool $disablePrivileges = false;
    private array $routes;

    private $userRole = null;

    public function __construct(
        private readonly Acl $acl,
        private readonly UrlHelper $urlHelper,
        private readonly CurrentUserRepository $currentUserRepository,
        array $config,
    ) {
        $this->routes = [];
        foreach ($config['routes'] as $route) {
            $this->routes[$route['name']] = $route;
        }
        $this->disablePrivileges = isset($config['temp_config']['disable_privileges']) && $config['temp_config']['disable_privileges'] === true;
    }

    /**
     * @param array $items
     * @return []ElementInterface
     */
    public function getActionLinksFromRouteItems(array $items, array $knownParameters = []): array
    {
        $links = [];
        foreach($items as $item) {
            if (isset($item['disabled']) && $item['disabled'] === true) {
                $links[] = Html::actionDisabled($item['label']);
                continue;
            }
            if (isset($item['parameters'])) {
                $knownParameters = $item['parameters'] + $knownParameters;
            }
            $route = $this->getRoute($item['route']);
            if ($route) {
                $url = $this->getRouteUrl($item['route'], $this->getRouteParamsFromKnownParams($route, $knownParameters));

                $links[] = Html::actionLink($url, $item['label']);
            }
        }

        return $links;
    }

    public function getLateRouteUrl(string $name, array $paramLateMappings = [], BridgeInterface $bridge = null, $ignoreErrors = false): ?LateCall
    {
        $route = $this->getRoute($name);
        if (null === $route) {
            return null;
        }
        
        $routeParams = [];
        if (isset($route['params'])) {
            foreach ($route['params'] as $paramName) {
                if (isset($paramLateMappings[$paramName])) {
                    $lateName = $paramLateMappings[$paramName];
                    // file_put_contents('data/logs/echo.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . "$paramName -> $lateName\n", FILE_APPEND);
                } else {
                    $lateName = $paramName;
                }
                $routeParams[$paramName] = $lateName; 
            }
        }
        // file_put_contents('data/logs/echo.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . "$name -> " . print_r($routeParams, true) . "\n", FILE_APPEND);

        if ($bridge) {
            $params = [];
            foreach ($routeParams as $paramName => $lateName) {
                if ($lateName instanceof LateInterface) {
                    $params[$paramName] = $lateName;
                } else {
                    $params[$paramName] = $bridge->getLate($lateName);
                }
            }
            // file_put_contents('data/logs/echo.txt', __FUNCTION__ . '(' . __LINE__ . '): ' . "$name -> " . print_r($routeParams, true) . "\n", FILE_APPEND);
        } else {
            $params = Late::getRa($routeParams);
        }
        IF ($ignoreErrors) {
            return Late::method($this, 'tryGeneration', $name, $params);
        } else {
            return Late::method($this->urlHelper, 'generate', $name, $params);
        }
    }

    /**
     * @param string $name
     * @param array  $parameters Mix of fixed and late parameters (not using the stack)
     * @return \Zalt\Late\LateCall|null
     */
    public function getMixedLateRouteUrl(string $name, array $parameters = []): ?LateCall
    {
        $route = $this->getRoute($name);
        if (null === $route) {
            return null;
        }

        $late = false;
        $routeParams = [];
        if (isset($route['params'])) {
            foreach ($route['params'] as $paramName) {
                if (isset($parameters[$paramName])) {
                    $routeParams[$paramName] = $parameters[$paramName];
                    $late = $late || $parameters[$paramName] instanceof LateInterface;
                    
                } else {
                    throw new RuntimeException(sprintf(
                        "Route %s expects a parameter value for %s.",
                        $name, $paramName));
                }
            }
        }
        if ($late) {
            return Late::method($this->urlHelper, 'generate', $name, $routeParams);
        }
        return $this->urlHelper->generate($name, $routeParams);
    }
    
    /**
     * @param string $current  The route to compare to
     * @param string $relative Routes related to current, e.g. a different action in the same route.
     * @return string|null
     */
    public function getRelatedRoute(string $current, string $relative): ?string
    {
        if (isset($this->routes[$relative])) {
            return $relative;
        }
        if (str_contains($relative, '.')) {
            $cParts = explode('.', $current);
            $rParts = explode('.', $relative);

            $output = implode('.', array_splice($cParts, 0, -count($rParts), $rParts));
        } else {
            $output = substr($current, 0, strrpos($current, '.') + 1) . $relative;
        }
        if (isset($this->routes[$output])) {
            return $output;
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function getAllRoutePrivileges(): array
    {
        return self::getAllRoutePrivilegesFromConfig($this->routes);
    }

    /**
     * @return string[]
     */
    public static function getAllRoutePrivilegesFromConfig(array $configRoutes): array
    {
        $privileges = [];

        foreach ($configRoutes as $route) {
            if (isset($route['options']['privilege'])) {
                $label = $route['options']['privilegeLabel'] ?? $route['options']['privilege'];
                $privileges[$route['options']['privilege']] = $label;
            }
        }

        return $privileges;
    }

    public function getRoute(string $name): ?array
    {
        if (!$this->hasAccessToRoute($name)) {
            return null;
        }

        return $this->routes[$name];
    }

    public function getUncheckedRoute(string $name): array
    {
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException($name);
        }

        return $this->routes[$name];
    }

    public function getRouteParent(string $name, string $parentName = 'index'): ?array
    {
        return $this->getRouteSibling($name, $parentName);
    }

    public function getRouteSibling(string $name, string $siblingName = 'index'): ?array
    {
        $routeParts = explode('.', $name);
        $routeParts[count($routeParts)-1] = $siblingName;
        $parentRouteName = join('.', $routeParts);
        return $this->getRoute($parentRouteName);
    }

    public function getRouteSiblings(string $name): ?array
    {
        $routeParts = explode('.', $name);
        $partsCount = count($routeParts);
        array_pop($routeParts);

        $baseRouteName = join('.', $routeParts);

        $sibblingRoutes = array_filter($this->routes, function($routeName) use ($baseRouteName, $partsCount) {
            return (str_starts_with($routeName, $baseRouteName) && count(explode('.', $routeName)) === $partsCount);
        }, ARRAY_FILTER_USE_KEY );

        return $sibblingRoutes;
    }


    public function getRouteParamsFromKnownParams(array $newRoute, array $knownParams): array
    {
        $params = [];
        if (isset($newRoute['params'])) {

            foreach($newRoute['params'] as $param) {
                if (isset($knownParams[$param])) {
                    $params[$param] = $knownParams[$param];
                }
            }
        }
        return $params;
    }

    public function getRouteUrl(string $name, array $routeParams = [], array $queryParams = []): ?string
    {
        $route = $this->getRoute($name);

        return $route === null ? null : $this->urlHelper->generate($name, $routeParams, $queryParams);
    }

    public function getRouteUrlOnMatch(string $name, array $routeParams = [], array $queryParams = []): ?string
    {
        $route = $this->getRoute($name);

        if (null !== $route) {
            if (! isset($route['params']) || $this->hasMatchingParameters($route['params'], array_keys($routeParams))) {
                return $this->urlHelper->generate($name, $routeParams, $queryParams);
            }
        }
        return null;
    }

    public function hasAccessToRoute(string $name): bool
    {
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException($name);
        }

        $route = $this->routes[$name];

        return empty($route['options']['privilege']) || $this->hasPrivilege($route['options']['privilege']);
    }

    protected function hasMatchingParameters($requiredParams, $availableParamKeys): bool
    {
        return ! array_diff($requiredParams, $availableParamKeys);
    }

    public function hasPrivilege(string $resource): bool
    {
        if ($this->disablePrivileges || (false === $this->userRole)) {
            return true;
        }
        if (null == $this->userRole) {
            $user = $this->currentUserRepository->getCurrentUser();
            if ($user instanceof User) {
                $this->userRole = $user->getRole();
            } else {
                $this->userRole = false;
                return true;
            }
        }
        return $this->acl->isAllowed($this->userRole, $resource);
    }
    
    public function tryGeneration(string $name, array $params): string
    {
        try {
            return $this->urlHelper->generate($name, $params);
        } catch (RuntimeException $re) {
            error_log($re->getMessage());
            return $re->getMessage();
        }
    }
}
