<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Rescue\Http\FallbackHandlerInterface;
use SplQueue;

class Dispatcher implements DispatcherInterface
{
    /**
     * @var MiddlewareInterface[]|SplQueue
     */
    private $queue;

    /**
     * @var FallbackHandlerInterface
     */
    private $fallbackHandler;

    public function __construct(FallbackHandlerInterface $fallbackHandler)
    {
        $this->queue = new SplQueue();
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->queue->isEmpty()) {
            return $this->fallbackHandler->handle($request);
        }

        /** @var MiddlewareInterface $middleware */
        $middleware = $this->queue->dequeue();

        return $middleware->process($request, $this);
    }

    /**
     * @@inheritDoc
     */
    public function add(MiddlewareInterface $middleware): DispatcherInterface
    {
        $this->queue->enqueue($middleware);

        return $this;
    }
}
