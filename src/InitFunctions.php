<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems;

use Gems\Handlers\NotFoundHandler;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Psr\Container\ContainerInterface;
use Zalt\Base\BaseDir;

/**
 * @package    Gems
 * @subpackage
 * @since      Class available since version 1.0
 */
class InitFunctions
{
    /**
     * Setup middleware pipeline:
     *
     * @param Application $app
     * @param MiddlewareFactory $factory
     * @param ContainerInterface $container
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function pipeline(Application $app, MiddlewareFactory $factory, ContainerInterface $container): void
    {
        // The error handler should be the first (most outer) middleware to catch
        // all Exceptions.
        $app->pipe(ErrorHandler::class);
        $app->pipe(ServerUrlMiddleware::class);

        // Pipe more middleware here that you want to execute on every request:
        // - bootstrapping
        // - pre-conditions
        // - modifications to outgoing responses
        //
        // Piped Middleware may be either callables or service names. Middleware may
        // also be passed as an array; each item in the array must resolve to
        // middleware eventually (i.e., callable or service name).
        //
        // Middleware can be attached to specific paths, allowing you to mix and match
        // applications under a common domain.  The handlers in each middleware
        // attached this way will see a URI with the matched path segment removed.
        //
        // i.e., path of "/api/member/profile" only passes "/member/profile" to $apiMiddleware
        // - $app->pipe('/api', $apiMiddleware);
        // - $app->pipe('/docs', $apiDocMiddleware);
        // - $app->pipe('/files', $filesMiddleware);

        // Fix basedir:
        // BaseDir::setBaseDir('/dir');
        $path = BaseDir::getBaseDir();
        // $path = '/dir';
        if ($path) {
            /** @var \Mezzio\Helper\UrlHelper $helper */
            $helper = $container->get(\Mezzio\Helper\UrlHelper::class);
            $helper->setBasePath($path);
            $app->pipe($path, RouteMiddleware::class);
        } else {
            // Register the routing middleware in the middleware pipeline.
            // This middleware registers the Mezzio\Router\RouteResult request attribute.
            $app->pipe(RouteMiddleware::class);
        }

        // The following handle routing failures for common conditions:
        // - HEAD request but no routes answer that method
        // - OPTIONS request but no routes answer that method
        // - method not allowed
        // Order here matters; the MethodNotAllowedMiddleware should be placed
        // after the Implicit*Middleware.
        $app->pipe(ImplicitHeadMiddleware::class);
        $app->pipe(ImplicitOptionsMiddleware::class);
        $app->pipe(\Gems\Middleware\MethodNotAllowedMiddleware::class);

        // Seed the UrlHelper with the routing results:
        $app->pipe(UrlHelperMiddleware::class);

        // Add more middleware here that needs to introspect the routing results; this
        // might include:
        //
        // - route-based authentication
        // - route-based validation
        // - etc.

        $config = $container->get('config');
        if (isset($config['pipeline'])) {
            ksort($config['pipeline'], SORT_NUMERIC);
            foreach($config['pipeline'] as $middleware) {
                $app->pipe($middleware);
            }
        }

        $app->pipe(\Gems\Middleware\DbProfilerMiddleware::class);

        // Register the dispatch middleware in the middleware pipeline
        $app->pipe(DispatchMiddleware::class);

        // At this point, if no Response is returned by any middleware, the
        // NotFoundHandler kicks in; alternately, you can provide other fallback
        // middleware to execute.
        $app->pipe(NotFoundHandler::class);
    }

    /**
     * FastRoute route configuration
     *
     * @see https://github.com/nikic/FastRoute
     *
     * Setup routes with a single request method:
     *
     * $app->get('/', App\Handler\HomePageHandler::class, 'home');
     * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
     * $app->put('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.put');
     * $app->patch('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.patch');
     * $app->delete('/album/{id:\d+}', App\Handler\AlbumDeleteHandler::class, 'album.delete');
     *
     * Or with multiple request methods:
     *
     * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
     *
     * Or handling all request methods:
     *
     * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
     *
     * or:
     *
     * $app->route(
     *     '/contact',
     *     App\Handler\ContactHandler::class,
     *     Mezzio\Router\Route::HTTP_METHOD_ANY,
     *     'contact'
     * );
     *
     * @param Application $app
     * @param MiddlewareFactory $factory
     * @param ContainerInterface $container
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function routes(Application $app, MiddlewareFactory $factory, ContainerInterface $container): void
    {
        $config = $container->get('config');
        \Mezzio\Container\ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        //$app->get('/', App\Handler\HomePageHandler::class, 'home');
        //$app->get('/api/ping', App\Handler\PingHandler::class, 'api.ping');
    }
}