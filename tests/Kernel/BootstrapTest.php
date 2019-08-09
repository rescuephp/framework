<?php

declare(strict_types=1);

namespace Rescue\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use ReflectionException;
use Rescue\Container\Container;
use Rescue\Helper\Response\JsonResponse;
use Rescue\Helper\Response\ResponseFormatInterface;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\Factory\UploadedFileFactory;
use Rescue\Http\Factory\UriFactory;
use Rescue\Kernel\Bootstrap;
use Rescue\Kernel\Loaders\RequestLoader;
use Rescue\Kernel\Loaders\RouterStorageLoader;
use Rescue\Kernel\Loaders\ServerLoader;

final class BootstrapTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testBootstrap(): void
    {
        $bootstrap = new Bootstrap(
            new Container(),
            [
                RequestLoader::class,
                RouterStorageLoader::class,
                ServerLoader::class,
            ],
            [
                StreamFactoryInterface::class => StreamFactory::class,
                UriFactoryInterface::class => UriFactory::class,
                ResponseFactoryInterface::class => ResponseFactory::class,
                UploadedFileFactoryInterface::class => UploadedFileFactory::class,
                ServerRequestFactoryInterface::class => ServerRequestFactory::class,

                ResponseFormatInterface::class => JsonResponse::class,
            ]
        );

        $bootstrap->bootstrap();
        $this->assertEmpty($bootstrap->getMiddlewaresAfter());
        $this->assertEmpty($bootstrap->getMiddlewaresBefore());
    }
}
