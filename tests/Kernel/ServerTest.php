<?php

declare(strict_types=1);

namespace Rescue\Tests\Kernel;

use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rescue\Container\Container;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Http\FallbackHandlerInterface;
use Rescue\Kernel\BootstrapInterface;
use Rescue\Kernel\Exception\KernelException;
use Rescue\Kernel\Resolver;
use Rescue\Kernel\Server;
use RuntimeException;

final class ServerTest extends TestCase
{
    /**
     * @throws KernelException
     */
    public function testCli(): void
    {
        $resolver = new Resolver(null, $this->getDefaultClasses());
        $server = new Server($resolver);

        $this->expectOutputString('Not Found');
        $server->run();
    }

    /**
     * @throws KernelException
     * @runInSeparateProcess
     */
    public function testWeb(): void
    {
        $resolver = new Resolver(null, $this->getDefaultClasses());
        $server = $this
            ->getMockBuilder(Server::class)
            ->setConstructorArgs([$resolver])
            ->onlyMethods(['isCli'])
            ->getMock();

        $server->method('isCli')->willReturn(false);

        $this->expectOutputString('Not Found');

        $server->run();

        $this->assertEquals(['foo: bar'], xdebug_get_headers());
    }

    /**
     * @throws KernelException
     */
    public function testDispatcherMethod(): void
    {
        $resolver = new Resolver(null, $this->getDefaultClasses());
        $middleware = $this->getMiddleware('bar');
        $resolver->getMiddlewareDispatcher()->add($middleware);
        $server = new Server($resolver);
        $this->expectOutputString('bar');
        $server->run();
    }

    /**
     * @param array $middlewares
     * @param string $result
     * @throws KernelException
     * @dataProvider middlewaresProvider
     */
    public function testMiddlewaresConstructor($middlewares, string $result): void
    {
        $resolver = new Resolver(null, $this->getDefaultClasses(), $middlewares);
        $server = new Server($resolver);

        $this->expectOutputString($result);
        $server->run();
    }

    /**
     * @throws KernelException
     */
    public function testRegisterDefaultClasses(): void
    {
        $container = new Container();
        $testClass = $this->getFallbackHandler();
        $resolver = new Resolver($container,
            array_merge(
                $this->getDefaultClasses(),
                [
                    StreamFactoryInterface::class => StreamFactory::class,
                    UriFactoryInterface::class => UriFactory::class,
                    'test' => get_class($testClass),
                ]
            )
        );


        $server = new Server($resolver);

        $this->expectOutputString('Not Found');

        $server->run();

        $this->assertTrue($container->has(StreamFactoryInterface::class));
        $this->assertTrue($container->has(UriFactoryInterface::class));
        $this->assertTrue($container->has(ServerRequestFactoryInterface::class));
        $this->assertTrue($container->has('test'));
        $this->assertInstanceOf(get_class($testClass), $container->get('test'));
    }

    /**
     * @param mixed $bootstrap
     * @param string|null $exception
     * @throws KernelException
     * @dataProvider bootstrapProvider
     */
    public function testBootstrap($bootstrap, string $exception = null): void
    {
        $resolver = new Resolver(null, $this->getDefaultClasses(), [], $bootstrap);
        $server = new Server($resolver);

        if ($exception === null) {
            $this->assertTrue(true);
        } else {
            $this->expectExceptionMessage($exception);
        }

        $server->run();
    }

    public function bootstrapProvider(): Generator
    {
        yield [
            'bootstrap' => [
                new class implements BootstrapInterface
                {
                    public function setUp(): void
                    {
                        throw new RuntimeException('testing');
                    }
                },
            ],
            'exception' => 'testing',
        ];

        yield [
            'middlewares' => [TestBootstrap::class],
            'exception' => 'foo',
        ];

        yield [
            'middlewares' => [TestMiddleware::class],
            'exception' => null,
        ];
    }

    public function middlewaresProvider(): Generator
    {
        yield [
            'middlewares' => [$this->getMiddleware('foo')],
            'result' => 'foo',
        ];

        yield [
            'middlewares' => [TestMiddleware::class],
            'result' => 'TEST',
        ];

        yield [
            'middlewares' => [self::class],
            'result' => 'Not Found',
        ];
    }

    private function getDefaultClasses(): array
    {
        return [
            FallbackHandlerInterface::class => get_class($this->getFallbackHandler()),
        ];
    }

    private function getFallbackHandler(): FallbackHandlerInterface
    {
        return new class implements FallbackHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = (new ResponseFactory(new StreamFactory()))
                    ->createResponse(404);
                $response->getBody()->write('Not Found');
                $response = $response->withHeader('foo', 'bar');

                return $response;
            }
        };
    }

    private function getMiddleware(string $response): MiddlewareInterface
    {
        return new class ($response) implements MiddlewareInterface
        {
            /**
             * @var string
             */
            private $response;

            public function __construct(string $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = (new ResponseFactory(new StreamFactory()))
                    ->createResponse(200);
                $response->getBody()->write($this->response);

                return $response;
            }
        };
    }
}

class TestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = (new ResponseFactory(new StreamFactory()))->createResponse(200);
        $response->getBody()->write('TEST');

        return $response;
    }
}

class TestBootstrap implements BootstrapInterface
{
    public function setUp(): void
    {
        throw new RuntimeException('foo');
    }
}
