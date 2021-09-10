<?php

namespace HackPHP\Http\Response;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Swoole\Http\Response as SwooleResponse;

class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, $reasonPhrase);
    }

    public function createFromSwoole(SwooleResponse $response): ResponseInterface
    {
        return $this->createResponse()->useSwoole($response);
    }
}
