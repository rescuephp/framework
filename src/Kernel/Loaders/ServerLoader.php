<?php

declare(strict_types=1);

namespace Rescue\Kernel\Loaders;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;
use Rescue\Container\ContainerInterface;
use Rescue\Kernel\Bootstrap;
use Rescue\Kernel\HttpNotFoundHandler;
use Rescue\Kernel\LoaderInterface;
use Rescue\Kernel\Server;
use Rescue\Request\RequestHandler;
use Rescue\Response\ResponseFormatInterface;
use Rescue\Response\ResponseWrapperInterface;
use Rescue\Routing\RouterInterface;
use Rescue\Routing\RouterStorageInterface;

class ServerLoader implements LoaderInterface
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var ResponseFormatInterface
     */
    private $responseFormat;

    /**
     * @var RouterStorageInterface
     */
    private $routerStorage;

    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ServerRequestInterface $request,
        ResponseFormatInterface $responseFormat,
        RouterStorageInterface $routerStorage,
        Bootstrap $bootstrap,
        ContainerInterface $container
    ) {
        $this->request = $request;
        $this->responseFormat = $responseFormat;
        $this->routerStorage = $routerStorage;
        $this->bootstrap = $bootstrap;
        $this->container = $container;
    }

    /**
     * @throws ReflectionException
     */
    public function load(): void
    {
        $server = new Server($this->responseFormat);

        $router = $this->routerStorage->getRouter();

        if ($router instanceof RouterInterface) {
            $middlewares = array_merge(
                $this->bootstrap->getMiddlewaresBefore(),
                $router->getMiddlewareStorage()->getMiddlewares(),
                $this->bootstrap->getMiddlewaresAfter()
            );

            $handler = $this->container->add($router->getHandlerClass());
        } else {
            $middlewares = array_merge(
                $this->bootstrap->getMiddlewaresBefore(),
                $this->bootstrap->getMiddlewaresAfter()
            );

            $handler = $this->container->get(HttpNotFoundHandler::class);
        }

        if ($handler instanceof RequestHandler) {
            /** @var ResponseWrapperInterface $wrapper */
            $wrapper = $this->container->get(ResponseWrapperInterface::class);
            $handler->withWrapper($wrapper);
        }

        $server->run(
            $this->request,
            $handler,
            $this->instanceMiddlewares($middlewares)
        );
    }

    /**
     * @param string[] $middlewares
     * @return MiddlewareInterface[]
     * @throws ReflectionException
     */
    private function instanceMiddlewares(array $middlewares): array
    {
        $instances = [];

        foreach ($middlewares as $middlewareClass) {
            $instances[] = $this->container->add($middlewareClass);
        }

        return $instances;
    }
}
