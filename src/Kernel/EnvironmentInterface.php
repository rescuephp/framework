<?php

declare(strict_types=1);

namespace Rescue\Kernel;

interface EnvironmentInterface
{
    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null);

    public function has(string $name): bool;
}
