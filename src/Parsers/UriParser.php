<?php

namespace HackPHP\Http\Parsers;

use Swoole\Http\Request;
use HackPHP\Http\URI\UriFactory;
use Psr\Http\Message\UriInterface;

class UriParser
{
    private UriInterface $uri;

    public function __construct(UriFactory $factory)
    {
        $this->uri = $factory->createUri();
    }

    /**
     * Parse the swoole uri.
     *
     * @param  Request $request
     * @return UriInterface
     */
    public function __invoke(Request $request)
    {
        $server = $request->server;
        $headers = $request->header;

        $this->parseScheme($server);
        $this->parseHostAndPort($server, $headers);
        $this->parseQuery($server);

        return $this->uri;
    }

    private function parseScheme(array $server): void
    {
        $scheme = isset($server['https']) && $server['https'] !== 'off'
            ? 'https'
            : 'http';

        $this->uri = $this->uri->withScheme($scheme);

        unset($scheme);
    }

    private function parseHostAndPort(array $server, array $headers): void
    {
        if (isset($server['http_host'])) {
            $this->parseServerHttpHost($server);
        } elseif (isset($server['server_name'])) {
            $this->uri = $this->uri->withHost($server['server_name']);
        } elseif (isset($server['server_addr'])) {
            $this->uri = $this->uri->withHost($server['server_addr']);
        } elseif (isset($headers['host'])) {
            $this->parseHeaderHost($headers);
        }
        if (!isset($server['server_port'])) {
            return;
        }
        if ($this->uri->getPort() === null) {
            return;
        }
        $this->uri = $this->uri->withPort($server['server_port']);
    }

    private function parseServerHttpHost(array $server): void
    {
        $hostHeaderParts = explode(':', $server['http_host']);

        $this->uri = $this->uri->withHost($hostHeaderParts[0]);

        if (isset($hostHeaderParts[1])) {
            $this->uri = $this->uri->withPort((int)$hostHeaderParts[1]);
        }
    }

    private function parseHeaderHost(array $headers): void
    {
        if (strpos($headers['host'], ':') !== false) {
            [$host, $port] = explode(':', $headers['host'], 2);

            if ($port !== '80') {
                $this->uri = $this->uri->withPort((int)$port);
            }
        } else {
            $host = $headers['host'];
        }

        $this->uri = $this->uri->withHost($host);
    }

    private function parseQuery(array $server): void
    {
        $hasQuery = false;

        if (isset($server['request_uri'])) {
            $requestUriParts = explode('?', $server['request_uri']);
            $this->uri = $this->uri->withPath($requestUriParts[0]);

            if (isset($requestUriParts[1])) {
                $hasQuery = true;
                $this->uri = $this->uri->withQuery($requestUriParts[1]);
            }
        }

        if ($hasQuery) {
            return;
        }

        if (!isset($server['query_string'])) {
            return;
        }

        $this->uri = $this->uri->withQuery($server['query_string']);
    }
}
