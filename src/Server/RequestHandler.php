<?php

namespace HackPHP\Http\Server;

use Swoole\Http\Response;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use HackPHP\Http\Response\ResponseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * Middleware queue.
     *
     * @var mixed[]
     */
    protected array $middlewares = [];

    /**
     * Swoole HTTP Response.
     *
     * @var Response
     */
    protected Response $swooleResponse;

    /**
     * Create new RequestHandler.
     *
     * @param array $middlewares
     */
    public function __construct(array $middlewares, Response $response)
    {
        if (empty($middlewares)) {
            throw new InvalidArgumentException('$middlewares cannot be empty');
        }

        $this->middlewares = $middlewares;
        $this->swooleResponse = $response;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->middlewares);
        next($this->middlewares);

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        }

        if ($middleware instanceof RequestHandlerInterface) {
            return $middleware->handle($request);
        }

        if (is_callable($middleware)) {
            return $middleware($request, $this);
        }

        return (new ResponseFactory)->createFromSwoole($this->swooleResponse);
    }

    public function __invoke(RequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }
}
