<?php

namespace Rescue\Helper\Formatter;

use Rescue\Helper\Formatter\Exception\FormatterException;

interface FormatterInterface
{
    public function getContentType(): string;

    /**
     * @param mixed $message
     * @return string
     * @throws FormatterException
     */
    public function format($message): string;
}
