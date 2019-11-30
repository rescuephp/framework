<?php

declare(strict_types=1);

namespace Rescue\Kernel;

class BootstrapDispatcher
{
    /**
     * @var BootstrapInterface[]
     */
    private array $bootstrap = [];

    public function __construct(array $queue = [])
    {
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
        $this->bootstrap[] = $bootstrap;

        return $this;
    }
}

