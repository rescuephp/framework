<?php

namespace Rescue\Tests\Kernel;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Rescue\Container\Container;
use Rescue\Helper\Formatter\JsonFormatter;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Http\MiddlewareInterface;
use Rescue\Http\RequestHandlerInterface;
use Rescue\Http\ResponseInterface;
use Rescue\Http\ServerRequestInterface;
use Rescue\Kernel\Exception\InvalidRequestHandler;
use Rescue\Kernel\OutputResponse;
use Rescue\Kernel\RequestHandler;
use Rescue\Kernel\Server;
use Rescue\Routing\Middleware\MiddlewareStorage;
use Rescue\Routing\RouterItemStorage;
use function get_class;

final class ServerTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testBase(): void
    {
        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory($streamFactory);
        $requestFactory = new ServerRequestFactory(new UriFactory(), $streamFactory);
        $container = new Container();
        $middlewareStorage = new MiddlewareStorage([$this->getRequestHandlerMiddleware()]);
        $router = new RouterItemStorage($middlewareStorage, 'GET');
        $request = $requestFactory->createServerRequest('GET', '/test');
        $response = $responseFactory->createResponse();
        $formatter = new JsonFormatter();

        $handler = new class () extends RequestHandler
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->send('test message');
            }
        };

        $handler->withResponse($response);

        $router->get('/test', get_class($handler));
        $router->get('/test2', get_class($handler));
        $router->get('/test4', get_class($handler));
        $router->get('/', get_class($handler));

        $server = new Server($container, $request, $response, $router, $formatter);
        $routerItem = $server->findRouterItem();
        $response = $server->run($routerItem);

        $output = (new OutputResponse())->output($response, false);

        $this->assertEquals('"test message"', $output);
    }

    /**
     * @throws ReflectionException
     */
    public function testUrlWithParams(): void
    {
        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory($streamFactory);
        $requestFactory = new ServerRequestFactory(new UriFactory(), $streamFactory);
        $request = $requestFactory->createServerRequest('GET', '/test/user/13');
        $middlewareStorage = new MiddlewareStorage([$this->getRequestHandlerMiddleware()]);
        $response = $responseFactory->createResponse();
        $container = new Container();
        $router = new RouterItemStorage($middlewareStorage, 'GET');
        $formatter = new JsonFormatter();

        $handler = new class () extends RequestHandler
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->send($request->getQueryParams());
            }
        };

        $handler->withResponse($response);
        $router->get('/test', get_class($handler));
        $router->get('/test/{apiName}/{id}', get_class($handler));

        $server = new Server($container, $request, $response, $router, $formatter);
        $routerItem = $server->findRouterItem();
        $response = $server->run($routerItem);

        $this->assertEquals('{"apiName":"user","id":"13"}', (new OutputResponse())->output($response, false));
    }

    public function testRouterItemNotFound(): void
    {
        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory($streamFactory);
        $requestFactory = new ServerRequestFactory(new UriFactory(), $streamFactory);
        $request = $requestFactory->createServerRequest('GET', '/');
        $response = $responseFactory->createResponse();
        $container = new Container();
        $formatter = new JsonFormatter();
        $middlewareStorage = new MiddlewareStorage([$this->getRequestHandlerMiddleware()]);
        $router = new RouterItemStorage($middlewareStorage, 'GET');

        $server = new Server($container, $request, $response, $router, $formatter);

        $routerItem = $server->findRouterItem();
        $this->assertNull($routerItem);
    }

    /**
     * @throws ReflectionException
     */
    public function testUnknownErrorMessage(): void
    {
        $handler = new class() extends RequestHandler
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($request->getMethod() === 'GET') {
                    throw new InvalidArgumentException('unknown exception');
                }

                return $this->getResponse();
            }
        };

        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory($streamFactory);
        $requestFactory = new ServerRequestFactory(new UriFactory(), $streamFactory);
        $request = $requestFactory->createServerRequest('GET', '/');
        $response = $responseFactory->createResponse();
        $container = new Container();
        $formatter = new JsonFormatter();
        $middlewareStorage = new MiddlewareStorage([$this->getRequestHandlerMiddleware()]);
        $router = new RouterItemStorage($middlewareStorage, 'GET');
        $router->on('get', '/', get_class($handler));

        $server = new Server($container, $request, $response, $router, $formatter);

        $this->expectException(InvalidArgumentException::class);

        $routerItem = $server->findRouterItem();
        $server->run($routerItem);
    }


    /**
     * @runInSeparateProcess
     * @throws ReflectionException
     */
    public function testSendHeaders(): void
    {
        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory($streamFactory);
        $requestFactory = new ServerRequestFactory(new UriFactory(), $streamFactory);
        $request = $requestFactory->createServerRequest('GET', '/test');
        $response = $responseFactory->createResponse();
        $container = new Container();
        $formatter = new JsonFormatter();
        $middlewareStorage = new MiddlewareStorage([$this->getRequestHandlerMiddleware()]);
        $router = new RouterItemStorage($middlewareStorage, 'GET');

        $handler = new class () extends RequestHandler
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->send('test message');
            }
        };

        $handler->withResponseFormatter($formatter);
        $handler->withResponse($response);

        $router->get('/test', get_class($handler));

        $server = new Server($container, $request, $response, $router, $formatter);
        $routerItem = $server->findRouterItem();
        $response = $server->run($routerItem);

        (new OutputResponse())->output($response);
        $this->assertEquals(
            ['Content-Type: application/json'], xdebug_get_headers()
        );
    }

    /**
     * @runInSeparateProcess
     * @throws ReflectionException
     */
    public function testInvalidRequestHandler(): void
    {
        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory($streamFactory);
        $requestFactory = new ServerRequestFactory(new UriFactory(), $streamFactory);
        $request = $requestFactory->createServerRequest('GET', '/test');
        $response = $responseFactory->createResponse();
        $container = new Container();
        $formatter = new JsonFormatter();
        $middlewareStorage = new MiddlewareStorage([$this->getRequestHandlerMiddleware()]);
        $router = new RouterItemStorage($middlewareStorage, 'GET');

        $invalidHandler = new class ()
        {
        };

        $router->get('/test', get_class($invalidHandler));

        $server = new Server($container, $request, $response, $router, $formatter);

        $this->expectException(InvalidRequestHandler::class);

        $routerItem = $server->findRouterItem();
        $server->run($routerItem);
    }

    private function getRequestHandlerMiddleware(): MiddlewareInterface
    {
        return new class () implements MiddlewareInterface
        {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }
}
