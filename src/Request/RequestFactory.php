<?php

namespace HackPHP\Http\Request;

use HackPHP\Http\Request\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface;

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
}
