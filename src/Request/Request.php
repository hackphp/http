<?php

namespace HackPHP\Http\Request;

use HackPHP\Http\Message;
use HackPHP\Http\URI\UriFactory;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;

class Request extends Message implements RequestInterface
{
    /**
     * Allowed HTTP Methods.
     *
     * @var array
     */
    protected array $allowedMethods = [
        "GET",
        "HEAD",
        "POST",
        "PUT",
        "DELETE",
        "CONNECT",
        "OPTIONS",
        "TRACE",
        "PATCH"
    ];

    /**
     * @var string
     */
    protected string $requestTarget = '';

    /**
     * @var string
     */
    protected string $method = 'GET';

    /**
     * @var UriInterface
     */
    protected ?UriInterface $uri;

    /**
     * Create new Request.
     *
     * @param string $method
     * @param UriInterface|string|null $uri
     * @param array $headers
     * @param StreamInterface|resource|string|null $body
     */
    public function __construct(
        string $method = 'GET',
        $uri = null,
        array $headers = [],
        $body = null
    ) {
        $this->method = $this->filterMethod($method);
        $this->setUri($uri);
        $this->setAddedHeaders($headers);
        $this->setBody($body);

        if ($this->protocolVersion === '1.1') {
            $this->headers['host'] = [$this->getHostHeader()];
        }
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget !== null && $this->requestTarget !== '') {
            return $this->requestTarget;
        }

        if ($this->uri === null) {
            return '/';
        }

        $requestTarget = $this->uri->getPath();
        $query = $this->uri->getQuery();

        if ($query !== '') {
            $requestTarget .= '?' . $query;
        }

        if ($requestTarget !== '') {
            return $requestTarget;
        }
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        $clone = clone $this;
        $clone->method = $this->filterMethod($method);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface
    {
        if ($this->uri === null) {
            $this->uri = (new UriFactory)->createUri();
        }

        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        $this->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $clone;
        }

        return $clone->withHeader('Host', $clone->getHostHeader());
    }

    /**
     * Filter HTTP request method.
     *
     * @param  string $method
     * @return string
     * 
     * @todo Replace regix with in_array
     */
    protected function filterMethod(string $method): string
    {
        if (!in_array($method, $this->allowedMethods)) {
            throw new \InvalidArgumentException(sprintf(
                'HTTP Method %s is invalid',
                $method
            ));
        }

        return $method;
    }

    /**
     * Return the "Host" header value
     *
     * @return string
     */
    protected function getHostHeader(): string
    {
        $host = $this->uri->getHost();

        if ($host === '') {
            return '';
        }

        $port = $this->uri->getPort();

        if ($port !== null) {
            $host .= ':' . $port;
        }

        return $host;
    }

    /**
     * Return the string representation of the request.
     * 
     * @return string
     */
    public function __toString(): string
    {
        $request = sprintf(
            "%s %s HTTP/%s\r\n",
            $this->getMethod(),
            $this->getRequestTarget(),
            $this->getProtocolVersion()
        );

        foreach (array_keys($this->headers) as $header) {
            if (strtolower($header) === 'cookie') {
                $cookie = implode('; ', $this->getHeader('Cookie'));
                $request .= sprintf(
                    "%s: %s\r\n",
                    $header,
                    $cookie
                );
            } else {
                $request .= sprintf(
                    "%s: %s\r\n",
                    $header,
                    $this->getHeaderLine($header)
                );
            }
        }

        return sprintf(
            "%s\r\n%s",
            $request,
            $this->getBody()
        );
    }

    /**
     * Set the request uri.
     *
     * @param  UriInterface|string|null $uri
     * @return void
     */
    protected function setUri($uri)
    {
        if ($uri instanceof UriInterface) {
            $this->uri = $uri;
        } elseif (is_string($uri)) {
            $this->uri = (new UriFactory)->createUri($uri);
        } else {
            $this->uri = (new UriFactory)->createUri();
        }
    }
}
