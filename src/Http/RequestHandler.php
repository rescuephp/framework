<?php

declare(strict_types=1);

namespace Rescue\Http;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class RequestHandler implements RequestHandlerInterface
{
    private ?ResponseWrapperInterface $wrapper;

    public function withWrapper(ResponseWrapperInterface $wrapper): self
    {
        $this->wrapper = $wrapper;

        return $this;
    }

    /**
     * @param mixed $message
     * @param int $code
     * @return ResponseInterface
     */
    protected function response($message, int $code = StatusCode::STATUS_OK): ResponseInterface
    {
        return $this->wrapper->response($message, $code);
    }
}
