<?php

use Rescue\Helper\Json\Exception\DecodeError;
use Rescue\Helper\Json\Exception\EncodeError;

/**
 * @param mixed $value
 * @param int $options
 * @param int $depth
 * @return string
 * @throws EncodeError
 */
function jsonEncode($value, int $options = 0, int $depth = 512): string
{
    $result = json_encode($value, $options, $depth);

    if ($result === false) {
        throw new EncodeError(json_last_error_msg());
    }

    return $result;
}

/**
 * @param string $json
 * @param bool $assoc
 * @param int $depth
 * @param int $options
 * @return mixed
 * @throws DecodeError
 */
function jsonDecode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
{
    $result = json_decode($json, $assoc, $depth, $options);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new DecodeError(json_last_error_msg());
    }

    return $result;
}
