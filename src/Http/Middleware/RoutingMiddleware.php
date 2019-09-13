<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use Rescue\Container\ContainerInterface;
use Rescue\Routing\RouterInterface;
use Rescue\Routing\RouterStorageInterface;

class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var RouterStorageInterface
     */
    private $routerStorage;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        RouterStorageInterface $routerStorage,
        ContainerInterface $container
    ) {
        $this->routerStorage = $routerStorage;
        $this->container = $container;
    }

    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (($router = $this->routerStorage->getRouter()) instanceof RouterInterface) {
            /** @var RequestHandlerInterface $routerHandler */
            $routerHandler = $this->container->add($router->getHandlerClass());

            $dispatcher = new Dispatcher($routerHandler);
            foreach ($router->getMiddlewareStorage()->getMiddlewares() as $middleware) {
                $dispatcher->add($middleware);
            }

            return $dispatcher->handle($request);
        }

        return $handler->handle($request);
    }
}
