<?php

declare(strict_types=1);

namespace Rescue\Helper\Response;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ResponseWrapper
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
     * @param mixed $message
     * @param int $code
     * @return ResponseInterface
     * @throws Exception\ResponseFormatException
     */
    public function response($message, int $code = StatusCodeInterface::STATUS_OK): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code);

        $stream = $this->streamFactory->createStream(
            $this->responseFormat->format($message)
        );

        return $response->withBody($stream);
    }

}
