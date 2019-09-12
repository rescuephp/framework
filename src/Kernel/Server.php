<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rescue\Routing\Middleware\MiddlewareStorage;
use Rescue\Routing\RouterStorage;
use Rescue\Routing\RouterStorageInterface;

class Server
{
    /**
     * @var Resolver
     */
    private $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function run(): void
    {
        $request = $this->createRequest();
        $this->createRouterStorage($request);
        $this->resolver->getBootstrapDispatcher()->dispatch();
        $response = $this->resolver->getMiddlewareDispatcher()->handle($request);

        $this->outputResponse($response);
    }

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
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

        echo (string)$response->getBody();
    }

    private function createRequest(): ServerRequestInterface
    {
        /** @var ServerRequestFactoryInterface $requestFactory */
        $requestFactory = $this->resolver->getContainer()
            ->get(ServerRequestFactoryInterface::class);

        $request = $requestFactory
            ->createServerRequest(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/',
                $_SERVER ?? []
            )
            ->withQueryParams($_GET ?? []);

        $this->resolver->getContainer()
            ->addByInstance(ServerRequestInterface::class, $request);

        return $request;
    }

    private function createRouterStorage(
        ServerRequestInterface $request
    ): RouterStorageInterface {
        $middlewareStorage = new MiddlewareStorage();

        $routerStorage = new RouterStorage(
            $middlewareStorage,
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        $this->resolver->getContainer()
            ->addByInstance(RouterStorageInterface::class, $routerStorage);

        return $routerStorage;
    }
}
