<?php

declare(strict_types=1);

namespace Rescue\Http;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ResponseInterface;

interface ResponseWrapperInterface
{
    /**
     * @param $message
     * @param int $code
     * @return ResponseInterface
     */
    public function response($message, int $code = StatusCode::STATUS_OK): ResponseInterface;
}
