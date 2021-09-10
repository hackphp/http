<?php

namespace HackPHP\Http\Request;

use HackPHP\Http\Parsers\UploadedFilesParser;
use HackPHP\Http\Request\ServerRequest;
use HackPHP\Http\Stream\StreamFactory;
use HackPHP\Http\URI\UriFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Swoole\Http\Request;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, [], null, $serverParams);
    }

    /**
     * Create ServerRequest from Swoole Request.
     *
     * @param  Request $request
     * @return ServerRequestInterface
     */
    public function createFromSwoole(Request $request): ServerRequestInterface
    {
        $server = $request->server;
        $method = $server['request_method'] ?? 'GET';
        $headers = $request->header ?? [];
        $uri = (new UriFactory)->createFromSwoole($request);
        $body = (new StreamFactory)->createStream($request->rawContent());
        $files = (new UploadedFilesParser)($request->files ?? []);

        $serverRequest = new ServerRequest($method, $uri, $headers, $body, $server);
        $serverRequest = $serverRequest
            ->withCookieParams($request->cookie ?? [])
            ->withQueryParams($request->get ?? [])
            ->withParsedBody($request->post ?? [])
            ->withUploadedFiles($files);

        unset($server, $method, $headers, $uri, $body, $files);

        return $serverRequest;
    }
}
