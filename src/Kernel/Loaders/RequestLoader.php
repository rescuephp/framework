<?php

declare(strict_types=1);

namespace Rescue\Kernel\Loaders;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionException;
use Rescue\Container\ContainerInterface;
use Rescue\Helper\Json\Exception\DecodeException;
use Rescue\Http\Factory\ServerRequestFactory;
use Rescue\Kernel\LoaderInterface;

class RequestLoader implements LoaderInterface
{
    /**
     * @var ServerRequestFactory
     */
    private $requestFactory;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ServerRequestFactory $requestFactory, ContainerInterface $container)
    {
        $this->requestFactory = $requestFactory;
        $this->container = $container;
    }

    /**
     * @throws DecodeException
     * @throws ReflectionException
     */
    public function load(): void
    {
        $request = $this
            ->requestFactory
            ->createServerRequest(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/',
                $_SERVER ?? []
            )
            ->withQueryParams($_GET ?? [])
            ->withCookieParams($_COOKIE ?? []);

        if (in_array(
            $request->getMethod(),
            [
                RequestMethodInterface::METHOD_POST,
                RequestMethodInterface::METHOD_PATCH,
                RequestMethodInterface::METHOD_PUT,
            ],
            true)
        ) {
            $parsedBody = empty($_POST)
                ? $this->parsePhpInput($request)
                : $_POST;

            $request = $request->withParsedBody($parsedBody);
            $request = $request->withUploadedFiles($this->parseFiles());
        }

        $this->container->addByInstance(ServerRequestInterface::class, $request);
    }

    /**
     * @return UploadedFileInterface[]
     */
    private function parseFiles(): array
    {
        $files = [];

        if (!empty($_FILES)) {
            /** @var UploadedFileFactoryInterface $uploadedFileFactory */
            $uploadedFileFactory = $this->container->get(UploadedFileFactoryInterface::class);

            /** @var StreamFactoryInterface $streamFactory */
            $streamFactory = $this->container->get(StreamFactoryInterface::class);

            foreach ($_FILES as $file) {
                if (is_array($file['tmp_name'])) {
                    foreach ($file['tmp_name'] as $index => $tmpName) {
                        $files[] = $uploadedFileFactory
                            ->createUploadedFile(
                                $streamFactory->createStreamFromFile($tmpName),
                                $file[$index]['size'] ?? null,
                                $file[$index]['error'] ?? UPLOAD_ERR_OK,
                                $file[$index]['name'] ?? null,
                                $file[$index]['type'] ?? null
                            );
                    }
                } else {
                    $files[] = $uploadedFileFactory
                        ->createUploadedFile(
                            $streamFactory->createStreamFromFile($file['tmp_name']),
                            $file['size'] ?? null,
                            $file['error'] ?? UPLOAD_ERR_OK,
                            $file['name'] ?? null,
                            $file['type'] ?? null
                        );
                }
            }
        }

        return $files;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     * @throws DecodeException
     */
    private function parsePhpInput(ServerRequestInterface $request): array
    {
        $input = file_get_contents('php://input');
        $contentTypeHeaders = $request->getHeader('Content-Type');
        $contentType = array_shift($contentTypeHeaders);

        if ($contentType === 'application/json') {
            return jsonDecode($input, true);
        }

        return [];
    }
}
