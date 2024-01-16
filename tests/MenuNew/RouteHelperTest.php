<?php

namespace GemsTest\MenuNew;

use Gems\Fake\User;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\Menu\RouteNotFoundException;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Route;
use Psr\Http\Server\MiddlewareInterface;

class RouteHelperTest extends \PHPUnit\Framework\TestCase
{
    private function buildMockUserRepository()
    {
        $currentUserRepository = $this->createMock(CurrentUserRepository::class);
        $user = new User();
        $currentUserRepository->expects($this->any())->method('getCurrentUser')->willReturn($user);

        return $currentUserRepository;
    }

    private function buildRouteHelper(?string $userRole = null): RouteHelper
    {
        $config = [
            'routes' => [
                [
                    'name' => 'route-a',
                    'path' => '/route-a',
                    'allowed_methods' => ['GET'],
                ],
                [
                    'name' => 'route-b',
                    'path' => '/route-b',
                    'allowed_methods' => ['GET'],
                ],
                [
                    'name' => 'route-c',
                    'path' => '/route-c',
                    'allowed_methods' => ['GET'],
                    'options' => [
                        'privilege' => 'privilege-b',
                    ]
                ],
                [
                    'name' => 'route-d',
                    'path' => '/route-d',
                    'allowed_methods' => ['GET'],
                ],
            ],
        ];

        $currentUserRepository = $this->buildMockUserRepository();
        $currentUserRepository->getCurrentUser()->setRole($userRole);

        $acl = new Acl();
        $acl->addResource('privilege-b');
        $acl->addResource('privilege-c');
        $acl->addRole('role-a');
        $acl->addRole('role-b');

        $acl->allow('role-b', 'privilege-b');

        $router = new FastRouteRouter(null, null, $config);
        $urlHelper = new UrlHelper($router);
        foreach ($config['routes'] as $route) {
            $router->addRoute(new Route(
                $route['path'],
                $this->createMock(MiddlewareInterface::class),
                $route['allowed_methods'],
                $route['name']
            ));
        }

        return new RouteHelper($acl, $urlHelper, $currentUserRepository, $config);
    }

    public function testCanGetRoute()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->assertSame([
            'name' => 'route-b',
            'path' => '/route-b',
            'allowed_methods' => ['GET'],
        ], $routeHelper->getRoute('route-b'));
    }

    public function testGetRouteThrowsWhenNotExists()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->expectException(RouteNotFoundException::class);
        $routeHelper->getRoute('nonexistent-route');
    }

    public function testGetRouteWithoutPermissionReturnsRouteWithoutRole()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->assertSame('/route-b', $routeHelper->getRoute('route-b')['path']);
    }

    public function testGetRouteWithoutPermissionReturnsRouteWithRole()
    {
        $routeHelper = $this->buildRouteHelper('role-a');

        $this->assertSame('/route-b', $routeHelper->getRoute('route-b')['path']);
    }

    public function testGetRouteWithPermissionReturnsNullWithoutRole()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->assertNull($routeHelper->getRoute('route-c'));
    }

    public function testGetRouteWithPermissionReturnsNullWhenNotAllowed()
    {
        $routeHelper = $this->buildRouteHelper('role-a');

        $this->assertNull($routeHelper->getRoute('route-c'));
    }

    public function testGetRouteWithPermissionReturnsRouteWhenAllowed()
    {
        $routeHelper = $this->buildRouteHelper('role-b');

        $this->assertSame('/route-c', $routeHelper->getRoute('route-c')['path']);
    }

    public function testGetRouteUrlWithPermissionReturnsNullWithoutRole()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->assertNull($routeHelper->getRouteUrl('route-c'));
    }

    public function testGetRouteUrlWithPermissionReturnsNullWhenNotAllowed()
    {
        $routeHelper = $this->buildRouteHelper('role-a');

        $this->assertNull($routeHelper->getRouteUrl('route-c'));
    }

    public function testGetRouteUrlWithPermissionReturnsUrlWhenAllowed()
    {
        $routeHelper = $this->buildRouteHelper('role-b');

        $this->assertStringContainsString('/route-c', $routeHelper->getRouteUrl('route-c'));
    }

    public function testHasAccessToRouteThrowsWhenNotExists()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->expectException(RouteNotFoundException::class);
        $routeHelper->hasAccessToRoute('nonexistent-route');
    }

    public function testHasAccessToRouteWithoutPermissionReturnsTrueWithoutRole()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->assertTrue($routeHelper->hasAccessToRoute('route-b'));
    }

    public function testHasAccessToRouteWithoutPermissionReturnsTrueWithRole()
    {
        $routeHelper = $this->buildRouteHelper('role-a');

        $this->assertTrue($routeHelper->hasAccessToRoute('route-b'));
    }

    public function testHasAccessToRouteWithPermissionReturnsFalseWithoutRole()
    {
        $routeHelper = $this->buildRouteHelper();

        $this->assertFalse($routeHelper->hasAccessToRoute('route-c'));
    }

    public function testHasAccessToRouteWithPermissionReturnsFalseWhenNotAllowed()
    {
        $routeHelper = $this->buildRouteHelper('role-a');

        $this->assertFalse($routeHelper->hasAccessToRoute('route-c'));
    }

    public function testHasAccessToRouteWithPermissionReturnsTrueWhenAllowed()
    {
        $routeHelper = $this->buildRouteHelper('role-b');

        $this->assertTrue($routeHelper->hasAccessToRoute('route-c'));
    }

    public function testHasPermission()
    {
        $routeHelper = $this->buildRouteHelper('role-b');
        $this->assertTrue($routeHelper->hasPrivilege('privilege-b'));
        $this->assertFalse($routeHelper->hasPrivilege('privilege-c'));

        $routeHelper = $this->buildRouteHelper('role-a');
        $this->assertFalse($routeHelper->hasPrivilege('privilege-b'));
        $this->assertFalse($routeHelper->hasPrivilege('privilege-c'));

        $routeHelper = $this->buildRouteHelper();
        $this->assertFalse($routeHelper->hasPrivilege('privilege-b'));
        $this->assertFalse($routeHelper->hasPrivilege('privilege-c'));
    }
}
