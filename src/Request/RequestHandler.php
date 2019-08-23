<?php

declare(strict_types=1);

namespace Rescue\Request;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rescue\Response\Exception\ResponseFormatException;
use Rescue\Response\ResponseWrapperInterface;

abstract class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var ResponseWrapperInterface|null
     */
    private $wrapper;

    public function withWrapper(ResponseWrapperInterface $wrapper): self
    {
        $this->wrapper = $wrapper;

        return $this;
    }

    /**
     * @param mixed $message
     * @param int $code
     * @return ResponseInterface
     * @throws ResponseFormatException
     */
    public function response($message, int $code = StatusCodeInterface::STATUS_OK): ResponseInterface
    {
        return $this->wrapper->response($message, $code);
    }
}
