<?php

namespace Rescue\Helper\Formatter;

use Rescue\Helper\Formatter\Exception\FormatException;
use Throwable;

class JsonFormatter implements FormatterInterface
{
    public function getContentType(): string
    {
        return 'application/json';
    }

    /**
     * @param mixed $message
     * @return string
     * @throws FormatException
     */
    public function format($message): string
    {
        try {
            $result = jsonEncode($message);
        } catch (Throwable $exception) {
            throw new FormatException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        return $result;
    }
}
