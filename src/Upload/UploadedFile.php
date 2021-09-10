<?php

namespace HackPHP\Http\Upload;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use HackPHP\Http\Stream\StreamFactory;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    /**
     * @var int[]
     */
    protected array $errors = [
        \UPLOAD_ERR_OK,
        \UPLOAD_ERR_INI_SIZE,
        \UPLOAD_ERR_FORM_SIZE,
        \UPLOAD_ERR_PARTIAL,
        \UPLOAD_ERR_NO_FILE,
        \UPLOAD_ERR_NO_TMP_DIR,
        \UPLOAD_ERR_CANT_WRITE,
        \UPLOAD_ERR_EXTENSION,
    ];

    /**
     * @var string|null
     */
    protected ?string $clientFilename = null;

    /**
     * @var string|null
     */
    protected ?string $clientMediaType = null;

    /**
     * @var int
     */
    protected int $error;

    /**
     * @var int|null
     */
    protected ?int $size = null;

    /**
     * @var string|null
     */
    protected ?string $file = null;

    /**
     * @var bool
     */
    protected bool $moved = false;

    /**
     * @var StreamInterface|null
     */
    protected ?StreamInterface $stream = null;

    /**
     * Create new UoloadedFile.
     *
     * @param  StreamInterface|resource|string $data
     * @param  int $errorStatus
     * @param  int|null $size
     * @param  string|null $clientFilename
     * @param  string|null $clientMediaType
     */
    public function __construct(
        $file,
        int $errorStatus,
        ?int $size = null,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        $this->setError($errorStatus);
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($this->error === \UPLOAD_ERR_OK) {
            $this->setFileOrStream($file);
        }
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        return (new StreamFactory)->createStreamFromFile($this->file, "r+");
    }

    /**
     * @inheritDoc
     */
    public function moveTo($targetPath)
    {
        $this->validateActive();

        if (!$this->isStringNotEmpty($targetPath) || $targetPath == null) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        if ($this->file) {
            $this->moved = (php_sapi_name() == 'cli')
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        } else {
            $this->copyStream($targetPath);

            $this->moved = true;
        }

        if (false === $this->moved) {
            throw new RuntimeException(
                sprintf('Uploaded file could not be moved to %s', $targetPath)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * Set the error status.
     *
     * @param  int $errorStatus
     * @return void
     */
    protected function setError(int $errorStatus)
    {
        if (!in_array($errorStatus, $this->errors)) {
            throw new InvalidArgumentException("Invalid error status.");
        }

        $this->error = $errorStatus;
    }

    /**
     * Set the stream|file.
     *
     * @param  StreamInterface|resource|string $name
     * @return void
     */
    protected function setFileOrStream($file)
    {
        if ($file instanceof StreamInterface) {
            $this->stream = $file;
        } elseif (is_resource($file)) {
            $this->stream = (new StreamFactory)->createStreamFromResource($file);
        } elseif (is_string($file) && $file != '') {
            $this->file = $file;
        } else {
            throw new InvalidArgumentException("Invalid stream or file.");
        }
    }

    /**
     * Validate the file if is moved or not ok.
     * 
     * @throws \RuntimeException
     */
    protected function validateActive(): void
    {
        if (\UPLOAD_ERR_OK !== $this->error) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /**
     * Check if the target path is string and not empty.
     *
     * @param  string $targetPath
     * @return bool
     */
    protected function isStringNotEmpty($targetPath): bool
    {
        return is_string($targetPath) && !empty($targetPath);
    }

    /**
     * Copy the UploadedFile stream to the given target path.
     *
     * @param  string $targetPath
     * @return void
     */
    protected function copyStream(string $targetPath)
    {
        $stream = $this->getStream();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $to = (new StreamFactory)->createStreamFromFile($targetPath, "w");

        while (!$stream->eof()) {
            // read 1MB and write it
            if (!$to->write($stream->read(1000000))) {
                break;
            }
        }
    }
}
