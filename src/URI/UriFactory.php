<?php

namespace HackPHP\Http\URI;

use Swoole\Http\Request;
use Psr\Http\Message\UriInterface;
use HackPHP\Http\Parsers\UriParser;
use Psr\Http\Message\UriFactoryInterface;

class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    public function createFromSwoole(Request $request)
    {
        return (new UriParser($this))($request);
    }
}
