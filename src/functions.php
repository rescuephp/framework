<?php

declare(strict_types=1);

use Rescue\Helper\Json\Exception\DecodeException;
use Rescue\Helper\Json\Exception\EncodeException;

/**
 * @param mixed $value
 * @param int $options
 * @param int $depth
 * @return string
 * @throws EncodeException
 */
function jsonEncode($value, int $options = 0, int $depth = 512): string
{
    $result = json_encode($value, $options, $depth);

    if ($result === false) {
        throw new EncodeException(json_last_error_msg());
    }

    return $result;
}

/**
 * @param string $json
 * @param bool $assoc
 * @param int $depth
 * @param int $options
 * @return mixed
 * @throws DecodeException
 */
function jsonDecode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
{
    $result = json_decode($json, $assoc, $depth, $options);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new DecodeException(json_last_error_msg());
    }

    return $result;
}
