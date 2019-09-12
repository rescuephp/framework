<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface DispatcherInterface extends RequestHandlerInterface
{
    public function add(MiddlewareInterface $middleware): DispatcherInterface;
}
