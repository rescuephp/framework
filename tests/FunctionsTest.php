<?php

declare(strict_types=1);

namespace Rescue\Tests;

use PHPUnit\Framework\TestCase;
use Rescue\Helper\Json\Exception\DecodeException;
use Rescue\Helper\Json\Exception\EncodeException;
use stdClass;

final class FunctionsTest extends TestCase
{
    /**
     * @throws EncodeException
     */
    public function testJsonEncode(): void
    {
        $this->assertEquals('{"test":true}', jsonEncode(['test' => true]));
    }

    /**
     * @throws DecodeException
     */
    public function testJsonDecodeAssoc(): void
    {
        $this->assertEquals(['test' => true], jsonDecode('{"test":true}', true));
    }

    /**
     * @throws DecodeException
     */
    public function testJsonDecode(): void
    {
        $result = jsonDecode('{"test":true}');
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(true, $result->test);
    }

    /**
     * @throws DecodeException
     */
    public function testJsonDecodeException(): void
    {
        $this->expectException(DecodeException::class);
        jsonDecode('G@$G)I42gj42gj42g42gG}@$}G@P$G{P}{}"asd"');
    }
}
