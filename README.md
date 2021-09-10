# HackPHP Http
HackPHP Http Message. Compatible with PSR-7, PSR-15, and PSR-17

# Installation

```bash
composer require hackphp/http
```

# Usage

### Middleware Request Handler
```php

/** @var \Psr\Http\Message\ServerRequestInterface $request */
$request = (new ServerRequestFactory)->createFromSwoole($swooleRequest);

$middlewares = [
    new FirstMiddleware(),
    new SecondMiddleware(),
    function (ServerRequestInterface $request, RequestHandlerInterface $next) {
        return $next($request);
    }
];

$handler = new RequestHandler(
    $middlewares,
    $swooleResponse
);

$response = $handler->handle($request);

$response->send();
```

### Response
```php
// create normal response
$statusCode = 200;
$reasonPhrase = "Ok";
$response = (new ResponseFactory)->createResponse(
    $statusCode,
    $reasonPhrase
);

// create from swoole response
$response = (new ResponseFactory)->createFromSwoole($swooleResponse);

// Change the response body
$stream = (new StreamFactory)->createStream("Hack PHP");
$response = $response->withBody($stream);

// Change the status code
$response = $response->withStatus(400);
```

### Request
```php
// Create Request
$request = (new RequestFactory)->createRequest("GET", "/");

// Create Server Request
$request = (new ServerRequestFactory)->createServerRequest(
    "GET",
    "/",
    $_SERVER // server params
);

// Create Server Request from swoole request
$request = (new ServerRequestFactory)->createFromSwoole($swooleRequest);
```

### Stream
```php
// Create from string
$stream = (new StreamFactory)->createStream("Hack PHP");

// Create from file
$mode = 'r';
$stream = (new StreamFactory)->createStreamFromFile($filename, $mode);

// Create from resource
$stream = (new StreamFactory)->createStreamFromResource($resource);
```

### Uploaded File
```php
$file = (new UploadedFileFactory)->createUploadedFile(
    StreamInterface $stream,
    ?int $size = null,
    int $error = \UPLOAD_ERR_OK,
    ?string $clientFilename = null,
    ?string $clientMediaType = null
);
```

### URI
```php
// Create URI
$uri = (new UriFactory)->createUri("https://github.com/hackphp");

// Create From Swoole
$uri = (new UriFactory)->createFromSwoole($swooleRequest);
```
