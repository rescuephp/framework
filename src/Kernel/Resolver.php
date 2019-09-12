<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
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

    private const CORE_CLASSES = [
        DispatcherInterface::class => Dispatcher::class,
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
     * Server constructor.
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

        try {
            $this->registerDefaultClasses(
                array_merge(
                    self::HTTP_CLASSES,
                    $defaultClasses,
                    self::CORE_CLASSES,
                )
            );
            $this->registerMiddlewares($middlewares);
            $this->registerBootstrap($bootstrap);
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

    public function getBootstrapDispatcher(): BootstrapDispatcher
    {
        return $this->bootstrapDispatcher;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
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
}
