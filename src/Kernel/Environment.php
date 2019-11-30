<?php

declare(strict_types=1);

namespace Rescue\Kernel;

class Environment implements EnvironmentInterface
{
    private const VALUES_MAP = [
        'true' => true,
        'false' => false,
        'null' => null,
        'empty' => '',
    ];

    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, $default = null)
    {
        if (!$this->has($name)) {
            return $default;
        }

        return $this->parseValue($this->values[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function parseValue($value)
    {
        $lowerValue = strtolower($value);

        if ($lowerValue === 'null') {
            return null;
        }

        return self::VALUES_MAP[$lowerValue] ?? $value;
    }
}
