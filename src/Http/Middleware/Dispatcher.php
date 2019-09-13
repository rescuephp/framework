<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

class Dispatcher implements DispatcherInterface
{
    /**
     * @var MiddlewareInterface[]|SplQueue
     */
    private $queue = [];

    /**
     * @var RequestHandlerInterface
     */
    private $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->queue)) {
            return $this->fallbackHandler->handle($request);
        }

        /** @var MiddlewareInterface $middleware */
        $middleware = array_shift($this->queue);

        return $middleware->process($request, $this);
    }

    /**
     * @@inheritDoc
     */
    public function add(MiddlewareInterface $middleware): DispatcherInterface
    {
        $this->queue[] = $middleware;

        return $this;
    }
}
