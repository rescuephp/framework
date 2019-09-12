<?php

declare(strict_types=1);

namespace Rescue\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Rescue\Kernel\BootstrapDispatcher;
use Rescue\Kernel\BootstrapInterface;
use RuntimeException;

final class BootstrapDispatcherTest extends TestCase
{
    public function testSimple(): void
    {
        $dispatcher = new BootstrapDispatcher([
            new class implements BootstrapInterface
            {
                public function setUp(): void
                {
                    throw new RuntimeException('testing');
                }
            },
        ]);

        $this->expectExceptionMessage('testing');

        $dispatcher->dispatch();
    }
}
