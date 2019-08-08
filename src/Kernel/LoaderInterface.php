<?php

declare(strict_types=1);

namespace Rescue\Kernel;

interface LoaderInterface
{
    public function load(): void;
}
