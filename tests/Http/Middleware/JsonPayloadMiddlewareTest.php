<?php

declare(strict_types=1);

namespace Rescue\Tests\Http\Middleware;

use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Http\FallbackHandlerInterface;
use Rescue\Http\Middleware\JsonPayloadMiddleware;

final class JsonPayloadMiddlewareTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    private ?ServerRequestInterface $simpleRequest;

    public function setUp(): void
    {
        parent::setUp();

        $this->simpleRequest = null;

        $this->requestFactory = new ServerRequestFactory(
            new UriFactory(),
            new StreamFactory()
        );
    }

    /**
     * @dataProvider dataProvider
     * @param string $method
     * @param string|null $contentType
     * @param JsonPayloadMiddleware $middleware
     * @param mixed|null $result
     */
    public function testSimple(
        string $method,
        ?string $contentType,
        JsonPayloadMiddleware $middleware,
        $result
    ): void {
        $request = $this->requestFactory->createServerRequest($method, '/');

        if ($contentType !== null) {
            $request = $request->withHeader('Content-Type', $contentType);
        }

        $middleware->process($request, $this->getFallbackHandler());
        $this->assertEquals($result, $this->simpleRequest->getParsedBody());
    }

    public function dataProvider(): Generator
    {
        yield [
            'method' => 'GET',
            'contentType' => null,
            'middleware' => new JsonPayloadMiddleware(),
            'result' => null,
        ];

        yield [
            'method' => 'POST',
            'contentType' => 'application/xml',
            'middleware' => new JsonPayloadMiddleware(),
            'result' => null,
        ];

        $middleware = $this->getMockBuilder(JsonPayloadMiddleware::class)
            ->onlyMethods(['getParsedContent'])
            ->getMock();

        $middleware->method('getParsedContent')->willReturn(['foo' => 'bar']);

        yield [
            'method' => 'POST',
            'contentType' => 'application/json',
            'middleware' => $middleware,
            'result' => ['foo' => 'bar'],
        ];
    }

    private function getFallbackHandler(): FallbackHandlerInterface
    {
        $updateRequest = function (ServerRequestInterface $request): void {
            $this->simpleRequest = $request;
        };

        return new class ($updateRequest) implements FallbackHandlerInterface {
            /**
             * @var callable
             */
            private $updateRequest;

            public function __construct(callable $updateRequest)
            {
                $this->updateRequest = $updateRequest;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                call_user_func($this->updateRequest, $request);

                $responseFactory = new ResponseFactory(new StreamFactory());
                $response = $responseFactory->createResponse(404);
                $response->getBody()->write('Not Found');

                return $response;
            }
        };
    }
}
