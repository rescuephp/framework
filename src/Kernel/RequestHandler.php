<?php

namespace Rescue\Kernel;

use Rescue\Helper\Formatter\Exception\FormatterException;
use Rescue\Helper\Formatter\FormatterInterface;
use Rescue\Http\RequestHandlerInterface;
use Rescue\Http\ResponseInterface;

abstract class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var FormatterInterface
     */
    private $responseFormatter;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function withResponseFormatter(FormatterInterface $formatter): RequestHandler
    {
        $this->responseFormatter = $formatter;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function withResponse(ResponseInterface $response): RequestHandler
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @param mixed $message
     * @return ResponseInterface
     * @throws FormatterException
     */
    public function send($message): ResponseInterface
    {
        $response = $this->getResponse();

        $response
            ->getBody()
            ->write($this->responseFormatter->format($message));

        $this->response = $response
            ->withHeader(
                'Content-Type',
                $this->responseFormatter->getContentType()
            );

        return $this->response;
    }
}
