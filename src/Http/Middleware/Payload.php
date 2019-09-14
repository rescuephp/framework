<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;

abstract class Payload
{
    protected const CONTENT_TYPE = '';

    protected const ALLOWED_METHODS = [];

    /**
     * @return mixed
     */
    abstract public function getParsedContent();

    protected function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($this->needParse($request)) {
            return $request->withParsedBody($this->getParsedContent());
        }

        return $request;
    }

    protected function needParse(ServerRequestInterface $request): bool
    {
        return $this->checkAllowedMethods($request) && $this->checkContentType($request);
    }

    protected function checkContentType(ServerRequestInterface $request): bool
    {
        return in_array(static::CONTENT_TYPE, $request->getHeader('Content-Type'), true);
    }

    protected function checkAllowedMethods(ServerRequestInterface $request): bool
    {
        if (empty(static::ALLOWED_METHODS)) {
            return true;
        }

        return in_array($request->getMethod(), static::ALLOWED_METHODS, true);
    }
}
