<?php

declare(strict_types=1);

namespace Rescue\Kernel\Loaders;

use Psr\Http\Message\ServerRequestInterface;
use Rescue\Container\ContainerInterface;
use Rescue\Kernel\LoaderInterface;
use Rescue\Routing\Middleware\MiddlewareStorage;
use Rescue\Routing\RouterStorage;
use Rescue\Routing\RouterStorageInterface;

class RouterStorageLoader implements LoaderInterface
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ServerRequestInterface $request, ContainerInterface $container)
    {
        $this->request = $request;
        $this->container = $container;
    }

    public function load(): void
    {
        $middlewareStorage = new MiddlewareStorage();

        $routerStorage = new RouterStorage(
            $middlewareStorage,
            $this->request->getMethod(),
            $this->request->getUri()->getPath()
        );

        $this->container->addByInstance(RouterStorageInterface::class, $routerStorage);
    }
}
