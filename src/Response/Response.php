<?php

namespace HackPHP\Http\Response;

use HackPHP\Http\Message;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response as SwooleResponse;

class Response extends Message implements ResponseInterface
{
    /**
     * Map of standard HTTP status code/reason phrases
     *
     * @var array
     */
    protected $phrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated to 306 => '(Unused)'
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        // SERVER ERROR
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * Status code reason phrase.
     * 
     * @var string
     */
    protected string $reasonPhrase = "";

    /**
     * Response status code.
     * 
     * @var int
     */
    protected int $statusCode = 200;

    /**
     * Swoole Response.
     *
     * @var SwooleResponse
     */
    protected SwooleResponse $response;

    /**
     * Create new Response.
     * 
     * @param int $code
     * @param string $reasonPhrase
     * @param array $headers
     * @param StreamInterface|resource|string|null $body
     */
    public function __construct(
        int $statusCode,
        string $reasonPhrase = '',
        array $headers = [],
        $body = null
    ) {
        $code = $this->validateStatusCode($statusCode);
        $this->statusCode = $code;

        $this->reasonPhrase = empty($reasonPhrase)
            ? $this->phrases[$code]
            : $reasonPhrase;

        $this->setAddedHeaders($headers);
        $this->setBody($body);
    }

    /**
     * Use swoole response.
     *
     * @param  SwooleResponse $response
     * @return $this
     */
    public function useSwoole(SwooleResponse $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = ''): Response
    {
        $code = $this->validateStatusCode($code);

        $clone = clone $this;

        $clone->statusCode = $code;
        $clone->reasonPhrase = empty($reasonPhrase)
            ? $this->phrases[$code]
            : $reasonPhrase;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Validate the status code.
     *
     * @param  int $code
     * @return int Valid status code.
     */
    protected function validateStatusCode(int $code)
    {
        if ($code === 306) {
            throw new InvalidArgumentException('Invalid status code! Status code 306 is unused.');
        }

        if (!array_key_exists($code, $this->phrases)) {
            throw new InvalidArgumentException("Status code [$code] is invalid");
        }

        return $code;
    }

    /**
     * Send chunked response to the client.
     *
     * @param  string $data
     * @return bool
     */
    public function chunk(string $data = ""): bool
    {
        return $this->response()->write($data);
    }

    /**
     * Send the response to the client and close the connection.
     * 
     * If the client enables keepalive, the connection between the server and
     * the client will maintained but if the client doesn't enable keepalive,
     * the connection between the server and the client will be closed.
     * 
     * If you call this method after `chunk` method, the response will be with
     * sent with chunk of length 0 to end data transmission with the client.
     *
     * @param  string $data
     * @return bool
     */
    public function send(string $data = "")
    {
        if (empty($data)) {
            $data = $this->getBody();
        }

        return $this->response()->end($data);
    }

    /**
     * Send a local server file directly to the client.
     *
     * @param  string $fileName
     * @return bool
     */
    public function sendFile(string $fileName)
    {
        return $this->response()->sendfile($fileName);
    }

    /**
     * Prepare swoole response with needed values.
     *
     * @return SwooleResponse
     */
    protected function response(): SwooleResponse
    {
        $this->response->header("Server", "HackPHP");

        if (!$this->hasHeader("Content-Type")) {
            $this->setHeaders(["Content-Type" => "text/html"]);
        }

        foreach ($this->getHeaders() as $name => $value) {
            $this->response->header($name, $this->getHeader($name));
        }

        $this->response->setStatusCode($this->statusCode, $this->reasonPhrase);

        return $this->response;
    }
}
