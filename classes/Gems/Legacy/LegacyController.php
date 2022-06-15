<?php

declare(strict_types=1);


namespace Gems\Legacy;


use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
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
    protected TemplateRendererInterface $template;

    public function __construct(ProjectOverloader $loader, TemplateRendererInterface $template, \Zend_View $view)
    {
        $this->container = $loader->getContainer();
        $this->loader = $loader;
        $this->view = $view;
        $this->template = $template;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $route  = $routeResult->getMatchedRoute();
        if ($route) {
            $options = $route->getOptions();
            if (isset($options['controller'], $options['action'])) {
                $controller = $options['controller'];
                $actionName = $options['action'] . 'Action';

                //$legacyRequest = $this->getLegacyRequest($request, $controller, $options['action']);
                $legacyResponse = new \Zend_Controller_Response_Http();

                $controllerObject = $this->loader->create($controller, $request, false);
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

                $content = $controllerObject->html->render($this->view);

                $data = [
                    'content' => $content,
                    'menuHtml' => null,
                ];

                $statusCode = 200;
                $headers = [];

                if ($this->template) {
                    return new HtmlResponse($this->template->render('app::legacy-view', $data), $statusCode, $headers);
                }

                return new HtmlResponse($content, $statusCode, $headers);
            }
        }

        return new EmptyResponse(404);
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
