<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UploadedFilesMiddleware implements MiddlewareInterface
{
    /**
     * @var UploadedFileFactoryInterface
     */
    private $uploadedFileFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(
        UploadedFileFactoryInterface $uploadedFileFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->uploadedFileFactory = $uploadedFileFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (!empty($_FILES)) {
            $request = $request->withUploadedFiles(
                iterator_to_array($this->getFiles($_FILES))
            );
        }

        return $handler->handle($request);
    }

    private function getFiles(array $uploadedFiles): Generator
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file['tmp_name'])) {
                foreach ($file['tmp_name'] as $index => $tmpName) {
                    yield $this->createFile($file[$index], $tmpName);
                }

                continue;
            }

            yield $this->createFile($file, $file['tmp_name']);
        }
    }

    private function createFile(array $file, string $tempName): UploadedFileInterface
    {
        return $this->uploadedFileFactory
            ->createUploadedFile(
                $this->streamFactory->createStreamFromFile($tempName),
                $file['size'] ?? null,
                $file['error'] ?? UPLOAD_ERR_OK,
                $file['name'] ?? null,
                $file['type'] ?? null
            );
    }
}
