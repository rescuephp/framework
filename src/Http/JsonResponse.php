<?php

declare(strict_types=1);

namespace Rescue\Http;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rescue\Helper\Json\Exception\EncodeException;

class JsonResponse implements ResponseWrapperInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @inheritDoc
     * @throws EncodeException
     */
    public function response($message, int $code = StatusCode::STATUS_OK): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code);
        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(jsonEncode($message));

        return $response;
    }
}
