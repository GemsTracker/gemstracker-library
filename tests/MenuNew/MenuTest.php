<?php

namespace GemsTest\MenuNew;

use Gems\MenuNew\Menu;
use Gems\MenuNew\MenuItemNotFoundException;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigRenderer;
use Psr\Http\Server\MiddlewareInterface;

class MenuTest extends \PHPUnit\Framework\TestCase
{
    private function buildMenuABC(?array $routes = null, ?array $menu = null): Menu
    {
        $menu ??= [
            [
                'name' => 'route-b',
                'label' => 'Route b',
                'type' => 'route-link-item',
            ]
        ];

        $router = $this->createMock(RouterInterface::class);

        $template = new TwigRenderer(null, 'html.twig');
        $template->addPath(__DIR__ . '/../../templates/menu', 'menu');

        $menuConfig = $this->createMock(\Gems\Config\Menu::class);
        $menuConfig->method('getItems')->willReturn($menu);

        $acl = $this->createMock(Acl::class);

        $config = [
            'routes' => $routes ?? [
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
                ],
            ],
        ];

        return new Menu($router, $template, $menuConfig, $acl, null, $config);
    }

    public function testCanRenderEmptyMenu()
    {
        $menu = $this->buildMenuABC([], []);

        $this->assertSame('', trim($menu->renderMenu()));
    }

    public function testCanGetRoute()
    {
        $menu = $this->buildMenuABC(null, []);

        $this->assertSame([
            'name' => 'route-b',
            'path' => '/route-b',
            'allowed_methods' => ['GET'],
        ], $menu->getRoute('route-b'));
    }

    public function testCanFindMenuItem()
    {
        $menu = $this->buildMenuABC();

        $menuItem = $menu->find('route-b');

        $this->assertSame('route-b', $menuItem->name);
    }

    public function testCannotFindMissingMenuItemForExistingRoute()
    {
        $menu = $this->buildMenuABC();

        $this->expectException(MenuItemNotFoundException::class);
        $menu->find('route-c');
    }

    public function testCannotFindMissingMenuItemForMissingRoute()
    {
        $menu = $this->buildMenuABC();

        $this->expectException(MenuItemNotFoundException::class);
        $menu->find('route-d');
    }

    private function buildMenuLarge(): Menu
    {
        $routes = [
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
            ],
            [
                'name' => 'route-d',
                'path' => '/route-d',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-e',
                'path' => '/route-e',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-f',
                'path' => '/route-f',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-g',
                'path' => '/route-g',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-g-a',
                'path' => '/route-g-a',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-g-b',
                'path' => '/route-g-b',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-g-c',
                'path' => '/route-g-c',
                'allowed_methods' => ['GET'],
            ],
        ];

        $menu = [
            [
                'name' => 'route-a',
                'label' => 'route-a',
                'type' => 'route-link-item',
                'children' => [
                    [
                        'name' => 'route-b',
                        'label' => 'route-b',
                        'type' => 'route-link-item',
                        'children' => [],
                    ],
                    [
                        'name' => 'route-c',
                        'label' => 'route-c',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'route-d',
                                'label' => 'route-d',
                                'type' => 'route-link-item',
                            ],
                        ],
                    ],
                    [
                        'name' => 'route-e',
                        'label' => 'route-e',
                        'type' => 'route-link-item',
                    ],
                ],
            ],
            [
                'name' => 'route-f',
                'label' => 'route-f',
                'type' => 'route-link-item',
            ],
            [
                'name' => 'route-g',
                'label' => 'route-g',
                'type' => 'route-link-item',
                'parent' => 'route-e',
                'children' => [
                    [
                        'name' => 'route-g-a',
                        'label' => 'route-g-a',
                        'type' => 'route-link-item',
                    ],
                    [
                        'name' => 'route-g-b',
                        'label' => 'route-g-b',
                        'type' => 'route-link-item',
                    ],
                    [
                        'name' => 'route-g-c',
                        'label' => 'route-g-c',
                        'type' => 'route-link-item',
                    ],
                ],
            ],
        ];

        return $this->buildMenuABC($routes, $menu);
    }

    public function testCanOpenRootRouteResult()
    {
        $menu = $this->buildMenuLarge();

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(new Route('route-a', $middlewareInterface));

        $menu->openRouteResult($routeResult);

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertTrue($menu->find('route-a')->isActive());
        $this->assertTrue($menu->find('route-b')->isOpen());
        $this->assertTrue($menu->find('route-c')->isOpen());
        $this->assertFalse($menu->find('route-d')->isOpen());
        $this->assertTrue($menu->find('route-e')->isOpen());
        $this->assertFalse($menu->find('route-f')->isOpen());
        $this->assertFalse($menu->find('route-g')->isOpen());
    }

    public function testCanOpenChildRouteResult()
    {
        $menu = $this->buildMenuLarge();

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(new Route('route-c', $middlewareInterface));

        $menu->openRouteResult($routeResult);

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertTrue($menu->find('route-b')->isOpen());
        $this->assertTrue($menu->find('route-c')->isOpen());
        $this->assertTrue($menu->find('route-c')->isActive());
        $this->assertTrue($menu->find('route-d')->isOpen());
        $this->assertTrue($menu->find('route-e')->isOpen());
        $this->assertFalse($menu->find('route-f')->isOpen());
        $this->assertFalse($menu->find('route-g')->isOpen());
    }

    public function testCanOpenNonMenuRouteResult()
    {
        $menu = $this->buildMenuLarge();

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(new Route('route-x', $middlewareInterface));

        $menu->openRouteResult($routeResult);

        $this->assertFalse($menu->find('route-a')->isOpen());
        $this->assertFalse($menu->find('route-b')->isOpen());
        $this->assertFalse($menu->find('route-c')->isOpen());
        $this->assertFalse($menu->find('route-d')->isOpen());
        $this->assertFalse($menu->find('route-e')->isOpen());
        $this->assertFalse($menu->find('route-f')->isOpen());
        $this->assertFalse($menu->find('route-g')->isOpen());
    }

    public function testRenderMenu()
    {
        $menu = $this->buildMenuLarge();

        $html = $menu->renderMenu();

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertFalse($menu->find('route-b')->isOpen());
        $this->assertFalse($menu->find('route-c')->isOpen());
        $this->assertFalse($menu->find('route-d')->isOpen());
        $this->assertFalse($menu->find('route-e')->isOpen());
        $this->assertTrue($menu->find('route-f')->isOpen());
        $this->assertFalse($menu->find('route-g')->isOpen());

        $this->assertStringContainsString('route-a', $html);
        $this->assertStringNotContainsString('route-b', $html);
        $this->assertStringContainsString('route-f', $html);
        $this->assertStringNotContainsString('route-g', $html);
    }

    public function testRenderMenuWithOpenRootRouteResult()
    {
        $menu = $this->buildMenuLarge();

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(new Route('route-a', $middlewareInterface));

        $menu->openRouteResult($routeResult);

        $html = $menu->renderMenu();

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertTrue($menu->find('route-b')->isOpen());
        $this->assertTrue($menu->find('route-c')->isOpen());
        $this->assertFalse($menu->find('route-d')->isOpen());
        $this->assertTrue($menu->find('route-e')->isOpen());
        $this->assertTrue($menu->find('route-f')->isOpen());
        $this->assertFalse($menu->find('route-g')->isOpen());

        $this->assertStringContainsString('route-a', $html);
        $this->assertStringContainsString('route-b', $html);
        $this->assertStringNotContainsString('route-d', $html);
        $this->assertStringContainsString('route-e', $html);
        $this->assertStringContainsString('route-f', $html);
        $this->assertStringNotContainsString('route-g', $html);
    }

    private function buildMenuWithIds()
    {
        $routes = [
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
                'name' => 'route-b-a',
                'path' => '/route-b/a',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-b-b',
                'path' => '/route-b/{id:\d+}/b',
                'allowed_methods' => ['GET'],
                'params' => ['id'],
            ],
            [
                'name' => 'route-b-c',
                'path' => '/route-b/{id:\d+}/c',
                'allowed_methods' => ['GET'],
                'params' => ['id'],
            ],
            [
                'name' => 'route-b-d',
                'path' => '/route-b/{id:\d+}/d',
                'allowed_methods' => ['GET'],
                'params' => ['id'],
            ],
            [
                'name' => 'route-b-c-a',
                'path' => '/route-b/{id:\d+}/c/{val:\d+}/a',
                'allowed_methods' => ['GET'],
                'params' => ['id', 'val'],
            ],
        ];

        $menu = [
            [
                'name' => 'route-a',
                'label' => 'route-a',
                'type' => 'route-link-item',
                'children' => [],
            ],
            [
                'name' => 'route-b',
                'label' => 'route-b',
                'type' => 'route-link-item',
                'children' => [
                    [
                        'name' => 'route-b-a',
                        'label' => 'route-b-a',
                        'type' => 'route-link-item',
                        'children' => [],
                    ],
                    [
                        'name' => 'route-b-b',
                        'label' => 'route-b-b',
                        'type' => 'route-link-item',
                        'children' => [],
                    ],
                    [
                        'name' => 'route-b-c',
                        'label' => 'route-b-c',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'route-b-c-a',
                                'label' => 'route-b-c-a',
                                'type' => 'route-link-item',
                            ],
                        ],
                    ],
                    [
                        'name' => 'route-b-d',
                        'label' => 'route-b-d',
                        'type' => 'route-link-item',
                    ],
                ],
            ],
        ];

        return $this->buildMenuABC($routes, $menu);
    }

    public function testOpeningWithoutParamsKeepsParamRoutesClosed()
    {
        $menu = $this->buildMenuWithIds();

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(new Route('route-b', $middlewareInterface));

        $menu->openRouteResult($routeResult);

        $html = $menu->renderMenu();

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertTrue($menu->find('route-b')->isOpen());
        $this->assertTrue($menu->find('route-b-a')->isOpen());
        $this->assertFalse($menu->find('route-b-b')->isOpen());

        $this->assertStringContainsString('route-a', $html);
        $this->assertStringContainsString('route-b', $html);
        $this->assertStringContainsString('route-b-a', $html);
        $this->assertStringNotContainsString('route-b-b', $html);
    }

    public function testOpeningWithSomeParamsOpensRelevantMenuItems()
    {
        $menu = $this->buildMenuWithIds();

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(new Route('route-b-b', $middlewareInterface), ['id' => 4]);

        $menu->openRouteResult($routeResult);

        $html = $menu->renderMenu();

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertTrue($menu->find('route-b')->isOpen());
        $this->assertTrue($menu->find('route-b-a')->isOpen());
        $this->assertTrue($menu->find('route-b-b')->isOpen());
        $this->assertTrue($menu->find('route-b-c')->isOpen());
        $this->assertFalse($menu->find('route-b-c-a')->isOpen());
        $this->assertTrue($menu->find('route-b-d')->isOpen());

        $this->assertStringContainsString('route-a', $html);
        $this->assertStringContainsString('route-b', $html);
        $this->assertStringContainsString('route-b-a', $html);
        $this->assertStringContainsString('route-b-b', $html);
        $this->assertStringContainsString('route-b-c', $html);
        $this->assertStringNotContainsString('route-b-c-a', $html);
        $this->assertStringContainsString('route-b-d', $html);
    }

    public function testOpeningWithFullParamsOpensAllMenuItems()
    {
        $menu = $this->buildMenuWithIds();

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(
            new Route('route-b-c-a', $middlewareInterface),
            ['id' => 4, 'val' => 6]
        );

        $menu->openRouteResult($routeResult);

        $html = $menu->renderMenu();

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertTrue($menu->find('route-b')->isOpen());
        $this->assertTrue($menu->find('route-b-a')->isOpen());
        $this->assertTrue($menu->find('route-b-b')->isOpen());
        $this->assertTrue($menu->find('route-b-c')->isOpen());
        $this->assertTrue($menu->find('route-b-c-a')->isOpen());
        $this->assertTrue($menu->find('route-b-d')->isOpen());

        $this->assertStringContainsString('route-a', $html);
        $this->assertStringContainsString('route-b', $html);
        $this->assertStringContainsString('route-b-a', $html);
        $this->assertStringContainsString('route-b-b', $html);
        $this->assertStringContainsString('route-b-c', $html);
        $this->assertStringContainsString('route-b-c-a', $html);
        $this->assertStringContainsString('route-b-d', $html);
    }

    private function buildMenuPermissions(?string $userRole): Menu
    {
        $routes = [
            [
                'name' => 'route-a',
                'path' => '/route-a',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-b',
                'path' => '/route-b',
                'allowed_methods' => ['GET'],
                'options' => [
                    'permission' => 'permission-b',
                ],
            ],
            [
                'name' => 'route-c',
                'path' => '/route-c',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-d',
                'path' => '/route-d',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-e',
                'path' => '/route-e',
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'route-f',
                'path' => '/route-f',
                'allowed_methods' => ['GET'],
                'options' => [
                    'permission' => 'permission-c',
                ],
            ],
        ];

        $menu = [
            [
                'name' => 'route-a',
                'label' => 'route-a',
                'type' => 'route-link-item',
                'children' => [
                    [
                        'name' => 'route-b',
                        'label' => 'route-b',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'route-c',
                                'label' => 'route-c',
                                'type' => 'route-link-item',
                                'children' => [
                                    [
                                        'name' => 'route-d',
                                        'label' => 'route-d',
                                        'type' => 'route-link-item',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'route-e',
                        'label' => 'route-e',
                        'type' => 'route-link-item',
                    ],
                ],
            ],
            [
                'name' => 'route-f',
                'label' => 'route-f',
                'type' => 'route-link-item',
            ],
        ];


        $router = $this->createMock(RouterInterface::class);
        $template = $this->createMock(TemplateRendererInterface::class);

        $menuConfig = $this->createMock(\Gems\Config\Menu::class);
        $menuConfig->method('getItems')->willReturn($menu);

        $acl = new Acl();
        $acl->addResource('permission-b');
        $acl->addResource('permission-c');
        $acl->addRole('role-a');
        $acl->addRole('role-b');

        $acl->allow('role-b', 'permission-b');

        $config = [
            'routes' => $routes,
        ];

        return new Menu($router, $template, $menuConfig, $acl, $userRole, $config);
    }

    public function dataProviderPermissionRoutes()
    {
        return [
            [null, false],
            ['role-a', false],
            ['role-b', true],
        ];
    }

    /**
     * @dataProvider dataProviderPermissionRoutes
     */
    public function testIsAllowed(?string $userRole, bool $outcome)
    {
        $menu = $this->buildMenuPermissions($userRole);

        $this->assertSame($outcome, $menu->isAllowed('permission-b'));
        $this->assertFalse($menu->isAllowed('permission-c'));
    }

    /**
     * @dataProvider dataProviderPermissionRoutes
     */
    public function testFollowsRoutePermissions(?string $userRole, bool $outcome)
    {
        $menu = $this->buildMenuPermissions($userRole);

        $middlewareInterface = $this->createMock(MiddlewareInterface::class);
        $routeResult = RouteResult::fromRoute(new Route('route-d', $middlewareInterface));

        $menu->openRouteResult($routeResult);

        $menu->renderMenu();

        $this->assertTrue($menu->find('route-a')->isOpen());
        $this->assertSame($outcome, $menu->find('route-b')->isOpen());
        $this->assertSame($outcome, $menu->find('route-c')->isOpen());
        $this->assertSame($outcome, $menu->find('route-d')->isOpen());
        $this->assertTrue($menu->find('route-e')->isOpen());
        $this->assertFalse($menu->find('route-f')->isOpen());
    }
}
