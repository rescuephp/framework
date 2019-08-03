<?php

namespace Rescue\Kernel;

use ReflectionException;
use Rescue\Container\ContainerInterface;
use Rescue\Helper\Formatter\FormatterInterface;
use Rescue\Http\RequestHandlerInterface;
use Rescue\Http\ResponseInterface;
use Rescue\Http\ServerRequestInterface;
use Rescue\Kernel\Exception\InvalidRequestHandler;
use Rescue\Routing\RouterItemInterface;
use Rescue\Routing\RouterItemStorageInterface;

class Server
{
    /**
     * @var RouterItemStorageInterface
     */
    private $router;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    public function __construct(
        ContainerInterface $container,
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouterItemStorageInterface $router,
        FormatterInterface $formatter
    ) {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
        $this->router = $router;
        $this->formatter = $formatter;
    }

    /**
     * @param RouterItemInterface $routerItem
     * @return ResponseInterface
     * @throws ReflectionException
     */
    public function run(RouterItemInterface $routerItem): ResponseInterface
    {
        $handlerInstance = $this->createRequestHandler($routerItem->getHandlerClass());

        foreach ($routerItem->getMiddlewareStorage()->getMiddlewares() as $middleware) {
            $this->response = $middleware->process($this->request, $handlerInstance);
        }

        return $this->response;
    }

    public function findRouterItem(): ?RouterItemInterface
    {
        $items = $this->router->getItems();

        foreach ($items as $item) {
            if (empty($item->getParamsNames()) && $item->getUri() === $this->request->getUri()->getPath()) {
                return $item;
            }

            if (preg_match($item->getRegExUri(), $this->request->getUri()->getPath(), $matches) === 1) {
                if (!empty($matches)) {
                    array_shift($matches);

                    $requestParams = $this->request->getQueryParams();

                    foreach ($item->getParamsNames() as $key => $name) {
                        $requestParams[$name] = $matches[$key] ?? null;
                    }

                    $this->request = $this->request->withQueryParams($requestParams);
                }

                return $item;
            }
        }

        return null;
    }

    /**
     * @param string $handlerClass
     * @return RequestHandlerInterface
     * @throws ReflectionException
     * @throws InvalidRequestHandler
     */
    private function createRequestHandler(
        string $handlerClass
    ): RequestHandlerInterface {
        $instance = $this->container->append($handlerClass);

        if (!$instance instanceof RequestHandlerInterface) {
            throw new InvalidRequestHandler("$handlerClass is not instance of " . RequestHandlerInterface::class);
        }

        if ($instance instanceof RequestHandler) {
            $instance->withResponse($this->response);
            $instance->withResponseFormatter($this->formatter);
        }

        return $instance;
    }
}
