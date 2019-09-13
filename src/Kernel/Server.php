<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use Psr\Http\Message\ResponseInterface;

class Server
{
    /**
     * @var Resolver
     */
    private $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function run(): void
    {
        $this->resolver->getBootstrapDispatcher()->dispatch();

        $request = $this->resolver->getRequest();
        $response = $this->resolver->getMiddlewareDispatcher()->handle($request);

        $this->outputResponse($response);
    }

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @param ResponseInterface $response
     */
    private function outputResponse(ResponseInterface $response): void
    {
        if (!$this->isCli()) {
            header("HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()}");

            foreach ($response->getHeaders() as $name => $headers) {
                header("$name: {$response->getHeaderLine($name)}");
            }
        }

        echo (string)$response->getBody();
    }

}
