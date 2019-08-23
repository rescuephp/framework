<?php

declare(strict_types=1);

namespace Rescue\Response;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ResponseWrapper implements ResponseWrapperInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var ResponseFormatInterface
     */
    private $responseFormat;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ResponseFormatInterface $responseFormat
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->responseFormat = $responseFormat;
    }

    /**
     * @inheritDoc
     */
    public function response(
        $message,
        int $code = StatusCodeInterface::STATUS_OK
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse($code);

        $stream = $this->streamFactory->createStream(
            $this->responseFormat->format($message)
        );

        return $response->withBody($stream);
    }

}
