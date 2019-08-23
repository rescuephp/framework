<?php

declare(strict_types=1);

namespace Rescue\Response;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Rescue\Response\Exception\ResponseFormatException;

interface ResponseWrapperInterface
{
    /**
     * @param $message
     * @param int $code
     * @return ResponseInterface
     *
     * @throws ResponseFormatException
     */
    public function response(
        $message,
        int $code = StatusCodeInterface::STATUS_OK
    ): ResponseInterface;
}
