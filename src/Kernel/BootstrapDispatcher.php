<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use SplObjectStorage;

class BootstrapDispatcher
{
    /**
     * @var SplObjectStorage|BootstrapInterface[]
     */
    private $bootstrap;

    public function __construct(array $queue = [])
    {
        $this->bootstrap = new SplObjectStorage();

        foreach ($queue as $boot) {
            $this->add($boot);
        }
    }

    public function dispatch(): void
    {
        foreach ($this->bootstrap as $bootstrap) {
            $bootstrap->setUp();
        }
    }

    public function add(BootstrapInterface $bootstrap): self
    {
        $this->bootstrap->attach($bootstrap);

        return $this;
    }
}

