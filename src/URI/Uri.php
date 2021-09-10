<?php

namespace HackPHP\Http\URI;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    /**
     * Uri scheme.
     * 
     * @var string
     */
    protected string $scheme = '';

    /**
     * The URI authority, in "[user-info@]host[:port]" format.
     * 
     * @var string
     */
    protected string $authority = '';

    /**
     * The URI user information, in "username[:password]" format.
     * 
     * @var string
     */
    protected string $userInfo = '';

    /**
     * The URI host.
     * 
     * @var string
     */
    protected string $host = '';

    /**
     * The URI port.
     * 
     * @var int|null
     */
    protected ?int $port = null;

    /**
     * The URI path.
     * 
     * @var string
     */
    protected string $path = '';

    /** The URI query string.
     * 
     * @var string
     */
    protected string $query = '';

    /**
     * The URI fragment.
     * 
     * @var string
     */
    protected string $fragment = '';

    /**
     * Create new Uri.
     *
     * @param string $uri
     */
    public function __construct(string $uri = '')
    {
        $this->uri = $uri;

        if ($uri == '') {
            return;
        }

        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException("URL is malformed.");
        }

        $this->scheme   = $this->filterScheme($parts['scheme'] ?? "");
        $this->host     = $this->filterHost($parts['host'] ?? "");
        $this->port     = $this->filterPort($parts['port'] ?? null);
        $this->userInfo = $this->filterUserInfo($parts['user'] ?? "", $parts['pass'] ?? "");
        $this->path     = $this->filterPath($parts['path'] ?? "");
        $this->query    = $this->filterQuery($parts['query'] ?? "");
        $this->fragment = $this->filterFragment($parts['fragment'] ?? "");
    }

    /**
     * @inheritDoc
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @inheritDoc
     */
    public function getAuthority()
    {
        $authority = $this->host;

        if ($authority === '') {
            return '';
        }

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        $clone = clone $this;
        $clone->scheme = $this->filterScheme($scheme);

        $clone->port
            = !$this->isStandardPort($scheme, $clone->port)
            ? $clone->port
            : null;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null)
    {
        $clone = clone $this;
        $clone->userInfo = $this->filterUserInfo($user, $password);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        $clone = clone $this;
        $clone->host = $this->filterHost($host);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        $clone = clone $this;
        $clone->port = $this->filterPort($port);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        $clone = clone $this;
        $clone->path = $this->filterPath($path);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        $clone = clone $this;
        $clone->query = $this->filterQuery($query);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        $clone = clone $this;
        $clone->fragment = $this->filterFragment($fragment);

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();

        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        if ($authority !== '' && strncmp($this->path, '/', 1) !== 0) {
            $uri .= '/' . $this->path;
        } elseif ($authority === '' && strncmp($this->path, '//', 2) === 0) {
            $uri .= '/' . ltrim($this->path, '/');
        } else {
            $uri .= $this->path;
        }

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Filter the uri scheme.
     *
     * @param  string $parts
     * @return string
     */
    protected function filterScheme($scheme): string
    {
        if ($scheme === '') {
            return '';
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*$/', $scheme)) {
            throw new InvalidArgumentException(
                'Scheme must be compliant with the "RFC 3986" standart'
            );
        }

        return strtolower($scheme);
    }

    /**
     * Filter the uri host.
     *
     * @param  string $parts
     * @return string
     */
    protected function filterHost($host): string
    {
        if ($host === '') {
            return '';
        }

        if (
            $this->isMatchedIPvFuture($host) ||
            $this->isMatchedIPv6address($host)
        ) {
            return strtolower("[{$host}]");
        }

        if ($this->isMatchedIPv4address($host)) {
            return strtolower($host);
        }

        // Matching a domain name.
        if (
            !preg_match(
                '/^([a-zA-Z0-9\-._~]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=])*$/',
                $host
            )
        ) {
            throw new \InvalidArgumentException(
                'Host must be compliant with the "RFC 3986" standart.'
            );
        }

        return strtolower($host);
    }

    /**
     * Filter the uri port.
     *
     * @param  int|null $port
     * @return int|null
     */
    protected function filterPort($port): ?int
    {
        if ($port == null) {
            return null;
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(
                'TCP or UDP port must be between 1 and 65535'
            );
        }

        return !$this->isStandardPort($this->scheme, $port) ? $port : null;
    }

    /**
     * Build the uri user info.
     *
     * @param  string $user
     * @param  string $password
     * @return string
     */
    protected function filterUserInfo($user, $password = null): string
    {
        if ($user == '' || $user == null) {
            return '';
        }

        $userInfo = $user;

        if ($password !== null && $password !== '') {
            $userInfo .= ':' . $password;
        }

        return $userInfo;
    }

    /**
     * Filter the uri path.
     *
     * @param  string $path
     * @return string
     */
    protected function filterPath($path): string
    {
        if ($this->scheme === '' && strncmp($path, ':', 1) === 0) {
            throw new InvalidArgumentException(
                'Path of a URI without a scheme cannot begin with a colon'
            );
        }

        $authority = $this->getAuthority();

        if ($authority === '' && strncmp($path, '//', 2) === 0) {
            throw new InvalidArgumentException(
                'Path of a URI without an authority cannot begin with two slashes'
            );
        }

        if ($authority !== '' && $path !== '' && strncmp($path, '/', 1) !== 0) {
            throw new InvalidArgumentException(
                'Path of a URI with an authority must be empty or begin with a slash'
            );
        }

        if (!($path !== '' && $path !== '/')) {
            return $path;
        }

        if (
            !preg_match(
                '/^([a-zA-Z0-9\-._~]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|\:|\@|\/|\%)*$/',
                $path
            )
        ) {
            throw new \InvalidArgumentException(
                'Path must be compliant with the "RFC 3986" standart'
            );
        }

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/%]++|%(?![a-fA-F0-9]{2}))/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $path
        );
    }

    /**
     * Filter the uri query.
     *
     * @param  string $query
     * @return string
     */
    protected function filterQuery($query): string
    {
        if ($query === '') {
            return '';
        }

        if (
            !preg_match(
                '/^([a-zA-Z0-9\-._~]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|\:|\@|\/|\?|\%)*$/',
                $query
            )
        ) {
            throw new \InvalidArgumentException(
                'Query must be compliant with the "RFC 3986" standart'
            );
        }

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/?%]++|%(?![a-fA-F0-9]{2}))/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $query
        );
    }

    /**
     * Filter the uri fragment.
     *
     * @param  string $fragment
     * @return string
     */
    protected function filterFragment($fragment): string
    {
        if ($fragment === '') {
            return '';
        }

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/?%]++|%(?![a-fA-F0-9]{2}))/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $fragment
        );
    }

    /**
     * Check if the given host is matching the IPvFuture.
     *
     * @param  string $host
     * @return bool
     */
    protected function isMatchedIPvFuture($host): bool
    {
        // Matching an IPvFuture or an IPv6address.
        if (!preg_match('/^\[.+\]$/', $host)) {
            return false;
        }

        if (!preg_match('/^(v|V)/', $host)) {
            return false;
        }

        if (
            !preg_match(
                '/^(v|V)[a-fA-F0-9]\.([a-zA-Z0-9\-._~]|[!$&\'()*+,;=]|\:)$/',
                $host
            )
        ) {
            throw new \InvalidArgumentException(
                'IP address must be compliant with the "IPvFuture" of the "RFC 3986" standart.'
            );
        }

        return true;
    }

    /**
     * Check if the given host is matching IPv6 address.
     *
     * @param  string $host
     * @return bool
     */
    protected function isMatchedIPv6address($host): bool
    {
        // Matching an IPvFuture or an IPv6address.
        if (!preg_match('/^\[.+\]$/', $host)) {
            return false;
        }


        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            throw new \InvalidArgumentException(
                'IP address must be compliant with the "IPv6address" of the "RFC 3986" standart.'
            );
        }

        return true;
    }

    /**
     * Check if the given host is matching IPv4 address.
     *
     * @param  string $host
     * @return bool
     */
    protected function isMatchedIPv4address($host): bool
    {
        if (
            !preg_match(
                '/^([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\./',
                $host
            )
        ) {
            return false;
        }

        if (
            filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            ) === false
        ) {
            throw new \InvalidArgumentException(
                'IP address must be compliant with the "IPv4address" of the "RFC 3986" standart.'
            );
        }

        return true;
    }

    /**
     * Check whether the port is standard for the given scheme.
     * 
     * @param  string  $scheme
     * @param  int|null $port
     * @return bool
     */
    protected function isStandardPort(string $scheme, ?int $port): bool
    {
        return $port === ($scheme === 'https' ? 443 : 80);
    }
}
