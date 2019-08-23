<?php

declare(strict_types=1);

namespace Rescue\Response;

use Rescue\Helper\Json\Exception\EncodeException;
use Rescue\Response\Exception\FormatException;

class JsonResponse implements ResponseFormatInterface
{
    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return 'application/json';
    }

    /**
     * @inheritDoc
     */
    public function format($message): string
    {
        try {
            $result = jsonEncode($message);
        } catch (EncodeException $exception) {
            throw new FormatException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        return $result;
    }
}
