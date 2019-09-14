<?php

declare(strict_types=1);

namespace Rescue\Http\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rescue\Helper\Json\Exception\DecodeException;

class JsonPayloadMiddleware extends Payload implements MiddlewareInterface
{
    protected const CONTENT_TYPE = 'application/json';

    protected const ALLOWED_METHODS = [
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_PUT,
    ];

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $request = $this->parse($request);

        return $handler->handle($request);
    }

    /**
     * @return mixed
     * @throws DecodeException
     */
    public function getParsedContent()
    {
        $content = file_get_contents('php://input');

        return jsonDecode($content, true);
    }
}
