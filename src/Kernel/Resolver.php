<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;
use Rescue\Container\Container;
use Rescue\Container\ContainerInterface;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UploadedFileFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Http\FallbackHandlerInterface;
use Rescue\Http\Middleware\Dispatcher;
use Rescue\Http\Middleware\DispatcherInterface;
use Rescue\Kernel\Exception\InstanceException;

class Resolver
{
    private const HTTP_CLASSES = [
        StreamFactoryInterface::class => StreamFactory::class,
        UriFactoryInterface::class => UriFactory::class,
        ResponseFactoryInterface::class => ResponseFactory::class,
        UploadedFileFactoryInterface::class => UploadedFileFactory::class,
        ServerRequestFactoryInterface::class => ServerRequestFactory::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var BootstrapDispatcher
     */
    private $bootstrapDispatcher;

    /**
     * Resolver constructor.
     * @param ContainerInterface|null $container
     * @param array $defaultClasses
     * @param string[]|MiddlewareInterface[] $middlewares
     * @param string[]|BootstrapInterface[] $bootstrap
     * @throws InstanceException
     */
    public function __construct(
        ContainerInterface $container = null,
        array $defaultClasses = [],
        array $middlewares = [],
        array $bootstrap = []
    ) {
        $this->container = $container ?? new Container();
        $this->bootstrapDispatcher = new BootstrapDispatcher();
        $this->container->addByInstance(ContainerInterface::class, $this->container);

        try {
            $this->registerDefaultClasses(
                array_merge(
                    self::HTTP_CLASSES,
                    $defaultClasses
                )
            );
            $this->registerMiddlewareDispatcher();
            $this->registerRequest();
            $this->registerBootstrap($bootstrap);
            $this->registerMiddlewares($middlewares);
        } catch (ReflectionException $exception) {
            throw new InstanceException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    public function getMiddlewareDispatcher(): DispatcherInterface
    {
        return $this->container->get(DispatcherInterface::class);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->container->get(ServerRequestInterface::class);
    }

    public function getBootstrapDispatcher(): BootstrapDispatcher
    {
        return $this->bootstrapDispatcher;
    }

    /**
     * @param array $classes
     * @throws ReflectionException
     */
    private function registerDefaultClasses(array $classes): void
    {
        foreach ($classes as $id => $className) {
            $this->container->add($id, $className);
        }
    }

    /**
     * @param array $bootstrap
     * @throws ReflectionException
     */
    private function registerBootstrap(array $bootstrap): void
    {
        foreach ($bootstrap as $item) {
            if (is_string($item)) {
                $item = $this->container->add($item);
            }

            if (!$item instanceof BootstrapInterface) {
                continue;
            }

            $this->bootstrapDispatcher->add($item);
        }
    }

    /**
     * @param array $middlewares
     * @throws ReflectionException
     */
    private function registerMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $middleware = $this->container->add($middleware);
            }

            if (!$middleware instanceof MiddlewareInterface) {
                continue;
            }

            $this->getMiddlewareDispatcher()->add($middleware);
        }
    }

    private function registerRequest(): void
    {
        /** @var ServerRequestFactoryInterface $requestFactory */
        $requestFactory = $this->container->get(ServerRequestFactoryInterface::class);

        $request = $requestFactory
            ->createServerRequest(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/',
                $_SERVER ?? []
            )
            ->withQueryParams($_GET ?? []);

        $this->container->addByInstance(ServerRequestInterface::class, $request);
    }

    /**
     * @throws ReflectionException
     * @throws InstanceException
     */
    private function registerMiddlewareDispatcher(): void
    {
        if ($this->container->has(DispatcherInterface::class)) {
            return;
        }

        if (!$this->container->has(FallbackHandlerInterface::class)) {
            throw new InstanceException(
                FallbackHandlerInterface::class . ' instance not found'
            );
        }

        $this->container->add(
            DispatcherInterface::class,
            Dispatcher::class,
            [
                $this->container->get(FallbackHandlerInterface::class),
            ]
        );
    }
}
