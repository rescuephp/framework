<?php

namespace Rescue\Kernel;

use Rescue\Http\ResponseInterface;

class OutputResponse
{
    public function output(ResponseInterface $response, bool $withHeaders = true): string
    {
        if ($withHeaders) {
            header("HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()}");

            foreach ($response->getHeaders() as $name => $headers) {
                header($name . ': ' . $response->getHeaderLine($name));
            }
        }

        return $response->getBody();
    }
}
