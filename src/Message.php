<?php

namespace HackPHP\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use HackPHP\Http\Stream\StreamFactory;
use Psr\Http\Message\MessageInterface;

abstract class Message implements MessageInterface
{
    /**
     * All headers.
     *
     * @var string[][]
     */
    protected array $headers = [];

    /**
     * Http Protocol Version.
     * 
     * @var string
     */
    protected string $protocolVersion = "1.1";

    /**
     * The message body.
     * 
     * @var StreamInterface
     */
    protected StreamInterface $body;

    /**
     * @inheritDoc
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version): self
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name): bool
    {
        return array_key_exists($this->formatKey($name), $this->headers);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        return $this->headers[$this->formatKey($name)];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name): string
    {
        return implode(',', $this->getHeader($name)) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value): self
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("Param 1 must be of type string");
        }

        $clone = clone $this;
        $clone->setHeaders([$name => $value]);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value): self
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("Param 1 must be of type string");
        }

        $clone = clone $this;
        $clone->setAddedHeaders([$name => $value]);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name): self
    {
        $clone = clone $this;
        if ($clone->hasHeader($name)) {
            unset($clone->headers[$this->formatKey($name)]);
        }

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): StreamInterface
    {
        // TODO: if the body == null return new Stream
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body): self
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * Format the given key string to lowercase.
     *
     * @param  string $key
     * @return string
     */
    private function formatKey($key)
    {
        return strtolower($key);
    }

    /**
     * Set to the headers (Append if exists.).
     *
     * @param  array $headers
     * @return void
     */
    protected function setAddedHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $this->headers[$this->formatKey($name)][] = $val;
                }
            } else {
                $this->headers[$this->formatKey($name)][] = $value;
            }
        }
    }

    /**
     * Set the header (replace if exists.).
     *
     * @param  array $headers
     * @return void
     */
    protected function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->headers[$this->formatKey($name)] = is_array($value) ? $value : [$value];
        }
    }

    /**
     * Set the request body.
     *
     * @param  StreamInterface|resource|string|null $body
     * @return void
     */
    protected function setBody($body)
    {
        $factory = new StreamFactory;

        if ($body instanceof StreamInterface) {
            $this->body = $body;
        } elseif (is_resource($body)) {
            $this->body = $factory->createStreamFromResource($body);
        } elseif (is_string($body)) {
            $this->body = $factory->createStream($body);
        } else {
            $this->body = $factory->createStream();
        }

        unset($factory);
    }
}
