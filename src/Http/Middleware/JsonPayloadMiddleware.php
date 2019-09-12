<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonPayloadMiddleware implements MiddlewareInterface
{
    protected const CONTENT_TYPE = 'application/json';

    protected const ALLOWED_METHODS = [
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_PUT,
    ];

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->checkContentType($request) && $this->checkAllowedMethods($request)) {
            $request = $request->withParsedBody(
                json_decode((string)$request->getBody(), true)
            );
        }

        return $handler->handle($request);
    }

    private function checkContentType(ServerRequestInterface $request): bool
    {
        return in_array(self::CONTENT_TYPE, $request->getHeader('Content-Type'), true);
    }

    private function checkAllowedMethods(ServerRequestInterface $request): bool
    {
        if (empty(self::ALLOWED_METHODS)) {
            return true;
        }

        return in_array($request->getMethod(), self::ALLOWED_METHODS, true);
    }
}
