<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rescue\Helper\Response\JsonResponse;
use Rescue\Helper\Response\ResponseFormatInterface;

class Server
{
    /**
     * @var ResponseFormatInterface
     */
    private $responseFormat;

    public function __construct(ResponseFormatInterface $responseFormat = null)
    {
        $this->responseFormat = $responseFormat ?? new JsonResponse();
    }

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @param MiddlewareInterface[] $middlewares
     */
    public function run(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        array $middlewares = []
    ): void {
        $response = null;

        foreach ($middlewares as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $response = $middleware->process($request, $handler);
            }
        }

        $this->outputResponse($response);
    }

    /**
     * @param ResponseInterface $response
     */
    private function outputResponse(ResponseInterface $response): void
    {
        if (!$this->isCli()) {
            header("HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()}");

            foreach ($response->getHeaders() as $name => $headers) {
                header($name . ': ' . $response->getHeaderLine($name));
            }

            header("Content-Type: {$this->responseFormat->getContentType()}");
        }

        echo $response->getBody();
    }
}
