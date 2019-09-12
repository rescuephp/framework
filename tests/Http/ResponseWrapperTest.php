<?php

declare(strict_types=1);

namespace Rescue\Tests\Http;

use Generator;
use PHPUnit\Framework\TestCase;
use Rescue\Helper\Json\Exception\EncodeException;
use Rescue\Http\Factory\ResponseFactory;
use Rescue\Http\Factory\StreamFactory;
use Rescue\Http\JsonResponse;

final class ResponseWrapperTest extends TestCase
{
    /**
     * @param mixed $message
     * @param string $result
     * @throws EncodeException
     * @dataProvider responseProvider
     */
    public function testResponse($message, string $result): void
    {
        $wrapper = new JsonResponse(new ResponseFactory(new StreamFactory()));
        $response = $wrapper->response($message);

        $this->assertEquals($result, (string)$response->getBody());
    }

    public function responseProvider(): Generator
    {
        yield [
            'message' => 'test',
            'result' => '"test"',
        ];

        yield [
            'message' => ['a' => 1],
            'result' => '{"a":1}',
        ];
    }
}
