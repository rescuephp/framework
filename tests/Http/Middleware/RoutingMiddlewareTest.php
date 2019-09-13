<?php

declare(strict_types=1);

namespace Rescue\Tests\Http\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use Rescue\Container\Container;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Http\FallbackHandlerInterface;
use Rescue\Http\Middleware\RoutingMiddleware;
use Rescue\Routing\Middleware\MiddlewareStorage;
use Rescue\Routing\RouterStorage;

final class RoutingMiddlewareTest extends TestCase
{
    /**
     * @var ServerRequestFactory
     */
    private $requestFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory(
            new UriFactory(),
            new StreamFactory()
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testFallback(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $middleware = new RoutingMiddleware(
            new RouterStorage(new MiddlewareStorage(), 'GET', '/'),
            new Container()
        );

        $response = $middleware->process($request, $this->getFallbackHandler());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', (string)$response->getBody());
    }

    /**
     * @throws ReflectionException
     */
    public function testRouting(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $storage = new RouterStorage(new MiddlewareStorage(), 'GET', '/');
        $storage->get('/', SuccessHandler::class);

        $middleware = new RoutingMiddleware($storage, new Container());

        $response = $middleware->process($request, $this->getFallbackHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', (string)$response->getBody());
    }

    private function getFallbackHandler(): FallbackHandlerInterface
    {
        return new class () implements FallbackHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $responseFactory = new ResponseFactory(new StreamFactory());
                $response = $responseFactory->createResponse(404);
                $response->getBody()->write('Not Found');

                return $response;
            }
        };
    }
}

class SuccessHandler implements RequestHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $responseFactory = new ResponseFactory(new StreamFactory());
        $response = $responseFactory->createResponse(200);
        $response->getBody()->write('Success');

        return $response;
    }
}
