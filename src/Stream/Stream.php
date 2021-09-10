<?php

namespace HackPHP\Http\Stream;

use Throwable;
use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /**
     * The readable stream modes.
     */
    protected const READ_MODES = ['r', 'r+', 'w+', 'a+', 'x+'];

    /**
     * The writable stream modes.
     */
    protected const WRITE_MODES = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+'];

    /**
     * The stream resource.
     *
     * @var resource|null
     */
    protected $resource;

    /**
     * The stream mode.
     * 
     * @var string
     */
    protected string $mode;

    /**
     * The stream size.
     *
     * @var int|null
     */
    protected ?int $size;

    /**
     * Whether the streem is seekable.
     *
     * @var bool
     */
    protected bool $seekable = false;

    /**
     * Whether the stream is writable.
     *
     * @var boolean
     */
    protected bool $writable = false;

    /**
     * Whether the stream is readable.
     * @var boolean
     */
    protected bool $readable = false;

    /**
     * Create new Stream.
     *
     * @param string|resource $content
     * @param string $mode
     * @param array $options
     */
    public function __construct($content = "", string $mode = "r+", array $options = [])
    {
        if (is_resource($content)) {
            $this->resource = $content;
        } elseif (is_string($content)) {
            $this->setResource($content, $mode);
        } else {
            throw new InvalidArgumentException("Stream resource must be valid PHP resource");
        }

        $this->setResourceMeta($options);
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->resource !== null && fclose($this->resource)) {
            $this->detach();
        }
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        $resource = $this->resource;

        if ($resource === null) {
            return $resource;
        }

        $this->resource = null;
        $this->size = null;
        $this->seekable = false;
        $this->writable = false;
        $this->readable = false;

        return $resource;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function tell(): int
    {
        if ($this->resource === null) {
            throw new RuntimeException("Stream resource is detached");
        }

        $position = ftell($this->resource);
        if ($position === false) {
            throw new RuntimeException(
                "Unable to tell the current position of the stream read/write pointer"
            );
        }

        return $position;
    }

    /**
     * @inheritDoc
     */
    public function eof(): bool
    {
        return $this->resource !== null || feof($this->resource);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream resource is detached');
        }

        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new RuntimeException('Can not seek to a position in the stream');
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * @inheritDoc
     */
    public function write($string): int
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream resource is detached');
        }

        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable');
        }

        $bytes = fwrite($this->resource, $string);

        if ($bytes === false) {
            throw new RuntimeException('Unable to write data to the stream');
        }

        $fstat = fstat($this->resource);
        if ($fstat === false) {
            $this->size = null;
        } else {
            $this->size = !empty($fstat['size']) ? $fstat['size'] : null;
        }

        return $bytes;
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * @inheritDoc
     */
    public function read($length): string
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream resource is detached');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        $data = fread($this->resource, $length);
        if ($data === false) {
            throw new RuntimeException('Unable to read data from the stream');
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream resource is detached');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        $contents = stream_get_contents($this->resource);
        if ($contents === false) {
            throw new RuntimeException('Unable to get contents of the stream');
        }

        return $contents;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream resource is detached');
        }

        $meta = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $meta;
        }

        return !empty($meta[$key]) ? $meta[$key] : null;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        try {
            if ($this->seekable) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Set the stream resource.
     *
     * @param  resource|string $content
     * @param  string $mode
     * @return void
     */
    protected function setResource($content, string $mode): void
    {
        if (
            is_file($content) ||
            strpos($content, 'php://') === 0
        ) {
            $mode = $this->validateMode($mode);


            $resource = fopen($content, $mode);
            if ($resource === false) {
                throw new RuntimeException(sprintf('Unable to create a stream from file [%s] !', $content));
            }
            $this->resource = $resource;
        }

        $resource = fopen('php://temp', $mode);
        if ($resource === false || fwrite($resource, $content) === false) {
            throw new RuntimeException('Unable to create a stream from string');
        }
        $this->resource = $resource;
    }

    /**
     * Check if the mode is valid.
     *
     * @param  string $mode
     * @return string
     */
    protected function validateMode(string $mode): string
    {
        if (
            !in_array($mode, static::READ_MODES) &&
            !in_array($mode, static::WRITE_MODES)
        ) {
            throw new InvalidArgumentException("Invalid mode $mode");
        }

        return $mode;
    }

    /**
     * Set the stream properties.
     *
     * @param  array $options
     * @return void
     */
    protected function setResourceMeta(array $options): void
    {
        $meta = $this->getMetadata();

        $this->setSize($options);
        $this->setSeekable($options, $meta);
        $this->setWritable($options, $meta);
        $this->setReadable($options, $meta);
    }

    /**
     * Set the stream size.
     *
     * @param  array $options
     * @return void
     */
    private function setSize(array $options): void
    {
        if (
            isset($options['size']) &&
            is_int($options['size']) &&
            $options['size'] > 0
        ) {
            $this->size = $options['size'];
            return;
        }

        $fstat = fstat($this->resource);

        if ($fstat === false) {
            $this->size = null;
        } else {
            $this->size = !empty($fstat['size']) ? $fstat['size'] : null;
        }
    }

    /**
     * Set seekable flag.
     *
     * @param  array $options
     * @param  array $meta
     * @return void
     */
    private function setSeekable(array $options, array $meta): void
    {
        if (isset($options['seekable']) && is_bool($options['seekable'])) {
            $this->seekable = $options['seekable'];
            return;
        }

        if (!empty($meta['seekable'])) {
            $this->seekable = $meta['seekable'];
        } else {
            $this->seekable = false;
        }
    }

    /**
     * Set writable flag.
     *
     * @param  array $options
     * @param  array $meta
     * @return void
     */
    private function setWritable(array $options, array $meta): void
    {
        if (isset($options['writable']) && is_bool($options['writable'])) {
            $this->writable = $options['writable'];
            return;
        }

        foreach (static::WRITE_MODES as $mode) {
            if (strncmp($meta['mode'], $mode, strlen($mode)) === 0) {
                $this->writable = true;
                break;
            }
        }
    }

    /**
     * Set readable flag.
     *
     * @param  array $options
     * @param  array $meta
     * @return void
     */
    private function setReadable(array $options, array $meta): void
    {
        if (isset($options['readable']) && is_bool($options['readable'])) {
            $this->readable = $options['readable'];
            return;
        }

        foreach (static::READ_MODES as $mode) {
            if (strncmp($meta['mode'], $mode, strlen($mode)) === 0) {
                $this->readable = true;
                break;
            }
        }
    }
}
