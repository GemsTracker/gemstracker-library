<?php

declare(strict_types=1);


namespace Gems\Legacy;


use Gems\Layout\LayoutRenderer;
use Gems\Middleware\LocaleMiddleware;
use Gems\Middleware\MenuMiddleware;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Controller\Action;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Loader\ProjectOverloader;

class LegacyController implements RequestHandlerInterface
{
    protected ContainerInterface $container;

    protected ProjectOverloader $loader;
    protected \Zend_View $view;
    private UrlHelper $urlHelper;
    private LayoutRenderer $layoutRenderer;

    public function __construct(ProjectOverloader $loader, LayoutRenderer $layoutRenderer, \Zend_View $view, UrlHelper $urlHelper)
    {
        $this->container = $loader->getContainer();
        $this->loader = $loader;
        $this->view = $view;
        $this->urlHelper = $urlHelper;
        $this->layoutRenderer = $layoutRenderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /**
         * @var RouteResult $routeResult
         */
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $route  = $routeResult->getMatchedRoute();
        if ($route) {
            $options = $route->getOptions();
            if (isset($options['controller'], $options['action'])) {
                $controller = $options['controller'];
                $actionName = $this->getActionName($options['action']);

                //$legacyRequest = $this->getLegacyRequest($request, $controller, $options['action']);
                $legacyResponse = new \Zend_Controller_Response_Http();

                /**
                 * @var \MUtil\Controller\Action $controllerObject
                 */
                $controllerObject = $this->loader->create($controller, $request, $this->urlHelper, false);
                $this->loadControllerDependencies($controllerObject);

                $controllerObject->init();

                if (method_exists($controllerObject, $actionName) && is_callable([$controllerObject, $actionName])) {
                    $response = call_user_func_array([$controllerObject, $actionName], []);
                    if ($response instanceof ResponseInterface) {
                        return $response;
                    }
                } else {
                    throw new \Exception(sprintf(
                        "Action %s not found in controller %s",
                        $actionName,
                        $controller,
                    ));
                }

                if ($controllerObject->getRedirectUrl() !== null) {
                    return new RedirectResponse($controllerObject->redirectUrl);
                }

                $content = $controllerObject->html->render($this->view);

                //$flashMessages = $this->getFlashMessages($request);

                $data = [
                    'content' => $content,
                ];

                $statusCode = 200;
                $headers = [];

                if ($this->layoutRenderer) {
                    return new HtmlResponse($this->layoutRenderer->render('gems::legacy-view', $request, $data), $statusCode, $headers);
                }

                return new HtmlResponse($content, $statusCode, $headers);
            }
        }

        return new EmptyResponse(404);
    }

    protected function getActionName(string $action): string
    {
        $actionParts = explode('-', $action);
        $capitalizedActionParts = array_map('ucfirst', $actionParts);
        return lcfirst(join('', $capitalizedActionParts)) . 'Action';
    }

    protected function getFlashMessages(ServerRequestInterface $request): array
    {
        /**
         * @var $messenger FlashMessagesInterface
         */
        $messenger = $request->getAttribute('flash');
        return $messenger->getFlash(Action::$messengerKey, []);
    }

    protected function getLegacyRequest(ServerRequestInterface $request, $controllerName, $actionName): \Zend_Controller_Request_Http
    {
        $requestWrapper = new RequestWrapper($request);

        $legacyRequest  = new \Zend_Controller_Request_Http();
        $legacyRequest->setControllerName($controllerName);
        $legacyRequest->setActionName($actionName);
        $legacyRequest->setParams($requestWrapper->getParams());

        return $legacyRequest;
    }

    protected function initLegacyConstants(): void
    {
        define('APPLICATION_PATH', '/');
        define('GEMS_PROJECT_NAME_UC', 'NewProject');
    }

    protected function loadControllerDependencies($object)
    {
        $objectProperties = get_object_vars($object);
        foreach ($objectProperties as $name => $value) {
            if ($value === null) {
                $legacyName = 'Legacy' . ucFirst($name);
                if ($this->container->has($legacyName)) {
                    $object->$name = $this->container->get($legacyName);
                }
            }
        }
    }
}
