<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use Rescue\Container\Container;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UploadedFileFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Http\Middleware\Dispatcher;
use Rescue\Routing\Middleware\MiddlewareStorage;
use Rescue\Routing\RouterStorage;
use Rescue\Routing\RouterStorageInterface;

class Server
{
    private const HTTP_CLASSES = [
        StreamFactoryInterface::class => StreamFactory::class,
        UriFactoryInterface::class => UriFactory::class,
        ResponseFactoryInterface::class => ResponseFactory::class,
        UploadedFileFactoryInterface::class => UploadedFileFactory::class,
        ServerRequestFactoryInterface::class => ServerRequestFactory::class,
    ];

    /**
     * @var ContainerInterface|Container
     */
    private $container;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var array|MiddlewareInterface[]|string[]
     */
    private $middlewares;

    /**
     * @var array
     */
    private $defaultClasses;

    /**
     * Server constructor.
     * @param RequestHandlerInterface $fallbackHandler
     * @param ContainerInterface $container
     * @param string[]|MiddlewareInterface[] $middlewares
     * @param array $defaultClasses
     */
    public function __construct(
        RequestHandlerInterface $fallbackHandler,
        ContainerInterface $container = null,
        array $middlewares = [],
        array $defaultClasses = []
    ) {
        $this->container = $container ?? new Container();
        $this->dispatcher = new Dispatcher($fallbackHandler);
        $this->middlewares = $middlewares;
        $this->defaultClasses = $defaultClasses;
    }

    /**
     * @throws ReflectionException
     */
    public function run(): void
    {
        $this->registerMiddlewares($this->middlewares);
        $this->registerDefaultClasses($this->defaultClasses);

        $request = $this->createRequest();
        $this->createRouterStorage($request);

        $response = $this->dispatcher->handle($request);

        $this->outputResponse($response);
    }

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    public function getMiddlewareDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    /**
     * @param ResponseInterface $response
     */
    private function outputResponse(ResponseInterface $response): void
    {
        if (!$this->isCli()) {
            header("HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()}");

            foreach ($response->getHeaders() as $name => $headers) {
                header("$name: {$response->getHeaderLine($name)}");
            }
        }

        echo $response->getBody();
    }

    /**
     * @param string[]|MiddlewareInterface[] $middlewares
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

            $this->dispatcher->add($middleware);
        }
    }

    /**
     * @param array $defaultClasses
     * @throws ReflectionException
     */
    private function registerDefaultClasses(array $defaultClasses): void
    {
        foreach ($defaultClasses as $id => $className) {
            $this->container->add($id, $className);
        }

        foreach (self::HTTP_CLASSES as $id => $className) {
            if ($this->container->has($id)) {
                continue;
            }

            $this->container->add($id, $className);
        }
    }

    private function createRequest(): ServerRequestInterface
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

        return $request;
    }

    private function createRouterStorage(ServerRequestInterface $request): RouterStorageInterface
    {
        $middlewareStorage = new MiddlewareStorage();

        $routerStorage = new RouterStorage(
            $middlewareStorage,
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        $this->container->addByInstance(RouterStorageInterface::class, $routerStorage);

        return $routerStorage;
    }
}
