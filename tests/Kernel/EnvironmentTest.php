<?php

declare(strict_types=1);

namespace Rescue\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Rescue\Kernel\Environment;

final class EnvironmentTest extends TestCase
{
    public function testBasic(): void
    {
        $env = new Environment(['a' => 'null', 'b' => 'false', 'c' => 'test_env']);

        $this->assertTrue($env->has('a'));
        $this->assertTrue($env->has('b'));
        $this->assertTrue($env->has('c'));
        $this->assertFalse($env->has('d'));

        $this->assertNull($env->get('a'));
        $this->assertFalse($env->get('b'));
        $this->assertEquals('test_env', $env->get('c'));
    }

    public function testDefault(): void
    {
        $env = new Environment(['a' => '1']);
        $this->assertEquals('default', $env->get('b', 'default'));
    }
}
