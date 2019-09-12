<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

class Dispatcher implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]|SplQueue
     */
    private $queue;
    /**
     * @var RequestHandlerInterface
     */
    private $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->queue = new SplQueue();
        $this->fallbackHandler = $fallbackHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->queue->isEmpty()) {
            return $this->fallbackHandler->handle($request);
        }

        /** @var MiddlewareInterface $middleware */
        $middleware = $this->queue->dequeue();

        return $middleware->process($request, $this);
    }

    public function add(MiddlewareInterface $middleware): self
    {
        $this->queue->enqueue($middleware);

        return $this;
    }
}
