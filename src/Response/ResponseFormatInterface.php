<?php

declare(strict_types=1);

namespace Rescue\Response;

use Rescue\Response\Exception\ResponseFormatException;

interface ResponseFormatInterface
{
    public function getContentType(): string;

    /**
     * @param mixed $message
     * @return string
     * @throws ResponseFormatException
     */
    public function format($message): string;
}
