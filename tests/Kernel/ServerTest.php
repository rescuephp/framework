<?php

declare(strict_types=1);

namespace Rescue\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rescue\Helper\Response\JsonResponse;
use Rescue\Helper\Response\ResponseWrapper;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Kernel\Server;

final class ServerTest extends TestCase
{
    /**
     * @var ServerRequestFactory
     */
    private $requestFactory;

    /**
     * @var ResponseWrapper
     */
    private $responseFactory;

    /**
     * @var JsonResponse
     */
    private $responseFormat;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $streamFactory = new StreamFactory();
        $this->requestFactory = new ServerRequestFactory(new UriFactory(), $streamFactory);
        $this->responseFormat = new JsonResponse();
        $this->responseFactory = new ResponseWrapper(
            new ResponseFactory($streamFactory),
            $streamFactory,
            $this->responseFormat
        );
    }

    public function testCli(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $handler = $this->getRequestHandler('hello world');

        $server = new Server();

        $this->expectOutputString('"hello world"');

        $server->run(
            $request,
            $handler,
            [$this->getRequestHandlerMiddleware()]
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testWeb(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $handler = $this->getRequestHandler(['test' => 1]);

        $server = $this
            ->getMockBuilder(Server::class)
            ->onlyMethods(['isCli'])
            ->getMock();

        $server->method('isCli')->willReturn(false);

        $this->expectOutputString('{"test":1}');

        $server->run(
            $request,
            $handler,
            [$this->getRequestHandlerMiddleware()]
        );

        $this->assertEquals(
            [
                'foo: bar',
                'Content-Type: application/json',
            ],
            xdebug_get_headers()
        );
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

    private function getRequestHandler($message): RequestHandlerInterface
    {
        return new class ($message, $this->responseFactory) implements RequestHandlerInterface
        {
            /**
             * @var mixed
             */
            private $message;

            /**
             * @var ResponseWrapper
             */
            private $response;

            public function __construct($message, ResponseWrapper $response)
            {
                $this->message = $message;
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this
                    ->response
                    ->response($this->message)
                    ->withHeader('foo', 'bar');
            }
        };
    }
}
