<?php

namespace HackPHP\Http\Request;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * Data related to the incoming request environment.
     *
     * @var array
     */
    protected array $serverParams;

    /**
     * Cookies sent by the client to the server.
     * 
     * @var array
     */
    protected array $cookieParams = [];

    /**
     * Deserialized query string arguments.
     * Get it from getUri()->getQuery() or from the QUERY_STRING server param.
     *
     * @var array
     */
    protected array $queryParams = [];

    /**
     * Uploaded Files.
     *
     * @var UploadedFileInterface[]
     */
    protected array $uploadedFiles = [];

    /**
     * @var null|array|object
     */
    protected $parsedBody;

    /**
     * Request attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Create new ServerRequest.
     *
     * @param string $method
     * @param UriInterface|string|null $uri
     * @param array $headers
     * @param StreamInterface|resource|string|null $body
     * @param array $serverParam
     */
    public function __construct(
        string $method = 'GET',
        $uri = null,
        array $headers = [],
        $body = null,
        array $serverParams = []
    ) {
        parent::__construct($method, $uri, $headers, $body);

        $this->serverParams = $serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        if (!is_array($data) && !is_object($data) && !is_null($data)) {
            throw new \InvalidArgumentException(
                'First parameter to withParsedBody MUST be object, array or null'
            );
        }

        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }
}
