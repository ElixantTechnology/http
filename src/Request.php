<?php
/**
 * Elixant Framework
 *
 * Elixant Framework is an extremely powerful, however easy-to-use
 * PHP-Baswed Application Development Framework that was created
 * as means to form a foundation of which all of Elixant's platforms
 * would be built on top of.
 *
 * Permission is hereby granted, free of charge, to any
 * person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the
 * Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit
 * persons to whom the Software is furnished to do so.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 *
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package      Elixant Framework
 * @copyright    2023 (C) Elixant Corporation.
 * @license      MIT License
 * @author       Alexander Schmautz <a.schmautz@outlook.com>
 */
declare(strict_types = 1);
namespace Elixant\HTTP;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Closure;
use Override;
use RuntimeException;
use Elixant\Utility\Arr;
use Elixant\Utility\IPAddress;
use Symfony\Component\VarDumper\VarDumper;
use Elixant\HTTP\Concerns\InteractsWithInput;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @package         Elixant/HTTP
 * @copyright       2024 (c) Elixant Corporation.
 * @license         MIT License
 * @author          Alexander M. Schmautz <a.schmautz91@gmail.com>
 * @class
 */
class Request extends SymfonyRequest
{
    use InteractsWithInput;
    
    protected ?string $json;
    
    /**
     * @var \Elixant\Utility\IPAddress|null The client's IP address or null if not available.
     */
    private ?IPAddress $ip;
    
    /**
     * Captures the current request.
     *
     * The static method `capture` enables the HTTP method parameter override and creates a new instance of the current
     * request based on the global variables.
     *
     * @return static The captured request instance.
     */
    public static function capture(): static
    {
        static::enableHttpMethodParameterOverride();
        
        return static::createFromGlobals();
    }
    
    /**
     * Retrieves the method.
     *
     * @return string The method.
     */
    public function method(): string
    {
        return $this->getMethod();
    }
    
    /**
     * Returns the root URL of the application.
     *
     * @return string The root URL of the application without trailing slashes.
     */
    public function root(): string
    {
        return rtrim($this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/');
    }
    
    /**
     * Returns the URL of the current request.
     *
     * @return string The URL of the current request without any query string parameters.
     */
    public function url(): string
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }
    
    /**
     * Returns the full URL of the current request.
     *
     * @return string The full URL of the current request.
     */
    public function fullUrl(): string
    {
        $query = $this->getQueryString();
        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';
        
        return $query ? $this->url() . $question . $query : $this->url();
    }
    
    /**
     * Returns the full URL of the application with query parameters added.
     *
     * @param array $query The query parameters to add to the URL.
     *
     * @return string The full URL of the application with query parameters.
     */
    public function fullUrlWithQuery(array $query): string
    {
        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';
        
        return count($this->query()) > 0
            ? $this->url() . $question . Arr::query(array_merge($this->query(), $query))
            : $this->fullUrl() . $question . Arr::query($query);
    }
    
    public function fullUrlWithoutQuery($keys): string
    {
        $query = Arr::except($this->query(), $keys);
        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';
        
        return count($query) > 0
            ? $this->url() . $question . Arr::query($query)
            : $this->url();
    }
    
    /**
     * Returns the path of the current request.
     *
     * @return string The path of the current request without leading or trailing slashes.
     */
    public function path(): string
    {
        $pattern = trim($this->getPathInfo(), '/');
        
        return $pattern === '' ? '/' : $pattern;
    }
    
    /**
     * Returns the decoded path of the current request.
     *
     * @return string The decoded path of the current request.
     */
    public function decodedPath(): string
    {
        return rawurldecode($this->path());
    }
    
    /**
     * Returns the segment at the given index from the URL path.
     *
     * If the segment at the given index does not exist, it will return the default value provided.
     * The index is 1-based, so the first segment is at index 1.
     *
     * @param int   $index   The index of the segment to retrieve.
     * @param mixed $default The default value to return if the segment does not exist. Defaults to null.
     *
     * @return string
     */
    public function segment(int $index, ?string $default = null): string
    {
        return Arr::get($this->segments(), $index - 1, $default);
    }
    
    /**
     * Returns an array of segments from the URL path.
     *
     * @return array An array containing the segments from the URL path.
     */
    public function segments(): array
    {
        $segments = explode('/', $this->decodedPath());
        
        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }
    
    /**
     * Returns the host of the application.
     *
     * @return string The host of the application.
     */
    public function host(): string
    {
        return $this->getHost();
    }
    
    /**
     * Returns the HTTP host of the application.
     *
     * @return string The HTTP host of the application.
     */
    public function httpHost(): string
    {
        return $this->getHttpHost();
    }
    
    /**
     * Returns the scheme and HTTP host of the application.
     *
     * @return string The scheme and HTTP host of the application.
     */
    public function schemeAndHttpHost(): string
    {
        return $this->getSchemeAndHttpHost();
    }
    
    /**
     * Determines if the request is an AJAX request.
     *
     * @return bool
     *   True if the request is an AJAX request, false otherwise.
     */
    public function ajax(): bool
    {
        return $this->isXmlHttpRequest();
    }
    
    /**
     * Returns true if the request is a PJAX request, false otherwise.
     *
     * @return bool True if the request is a PJAX request, false otherwise.
     */
    public function pjax(): bool
    {
        return $this->headers->get('X-PJAX') == true;
    }
    
    /**
     * Checks if the request is a prefetch request.
     *
     * @return bool Indicates if the request is a prefetch request.
     */
    public function prefetch(): bool
    {
        return strcasecmp($this->server->get('HTTP_X_MOZ') ?? '', 'prefetch') === 0
               || strcasecmp($this->headers->get('Purpose') ?? '', 'prefetch') === 0
               || strcasecmp($this->headers->get('Sec-Purpose') ?? '', 'prefetch') === 0;
    }
    
    /**
     * Returns whether the application is running in a secure mode.
     *
     * @return bool Whether the application is running in a secure mode.
     */
    public function secure(): bool
    {
        return $this->isSecure();
    }
    
    /**
     * {@inheritdoc}
     *
     * Override the getClientIps method to return an array of IPAddress objects.
     *
     * @return array An array of IPAddress objects representing the client IPs.
     *
     * @throws \Darsyn\IP\Exception\InvalidIpAddressException
     * @throws \Darsyn\IP\Exception\WrongVersionException
     */
    #[Override]
    public function getClientIps(): array
    {
        $ips = $this->getClientIps();
        foreach ($ips as $i => $ip) {
            $ips[$i] = IPAddress::factory($ip);
        }
        
        return $ips;
    }
    
    /**
     * Returns the IP address of the client.
     *
     * @return IPAddress The IP address of the client.
     *
     * @throws \Darsyn\IP\Exception\InvalidIpAddressException
     * @throws \Darsyn\IP\Exception\WrongVersionException
     */
    public function ip(): IPAddress
    {
        return ($this->getClientIps())[0];
    }
    
    /**
     * Returns the User-Agent header value.
     *
     * @return string The User-Agent header value.
     */
    public function userAgent(): string
    {
        return $this->headers->get('User-Agent');
    }
    
    /**
     * Returns the input source for the request.
     *
     * The input source is determined based on the HTTP method used in the request. If the HTTP method is either 'GET'
     * or 'HEAD', the request's query parameters are considered as the input source. Otherwise, the request's body
     * parameters are considered as the input source.
     *
     * @return InputBag The input source for the request, which is an instance of InputBag.
     */
    public function getInputSource(): InputBag
    {
        return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }
    
    /**
     * Converts the object to an array.
     *
     * @return array The object converted to an array.
     */
    public function toArray(): array
    {
        return $this->all();
    }
    
    /**
     * Returns the session of the application.
     *
     * @return \Closure|\Symfony\Component\HttpFoundation\Session\SessionInterface|null The session of the application
     *                                                                                  if present, a Closure callback
     *                                                                                  if the session is not set, or
     *                                                                                  null if the session is not
     *                                                                                  available.
     *
     * @throws \RuntimeException Thrown if the session store is not set on the request.
     */
    public function session(): Closure|SessionInterface|null
    {
        if ( ! $this->hasSession()) {
            throw new RuntimeException('Session store not set on request.');
        }
        
        return $this->session;
    }
    
    /**
     * Replaces the input source with the given array.
     *
     * @param array $input The array to replace the input source with.
     *
     * @return static The instance of the object.
     */
    public function replace(array $input): static
    {
        $this->getInputSource()->replace($input);
        
        return $this;
    }
    
    /**
     * Filters the files array recursively.
     *
     * @param array $files The array of files to filter.
     *
     * @return array|null The filtered files array.
     */
    protected function filterFiles(array $files): ?array
    {
        if ( ! $files) {
            return null;
        }
        foreach ($files as $key => $data) {
            if (is_array($data)) {
                $files[$key] = $this->filterFiles(files: $data);
            }
            if (empty($files[$key])) {
                unset($files[$key]);
            }
        }
        
        return $files;
    }
    
    /**
     * Sets the request locale.
     *
     * @param string $locale The locale to set for the request.
     *
     * @return void
     */
    public function setRequestLocale(string $locale): void
    {
        $this->locale = $locale;
    }
    
    /**
     * Sets the default request locale for the application.
     *
     * @param string $locale The default request locale to set.
     *
     * @return void
     */
    public function setDefaultRequestLocale(string $locale): void
    {
        $this->defaultLocale = $locale;
    }
    
    /**
     * Sets the JSON data.
     *
     * @param mixed $json The JSON data to set.
     *
     * @return static The updated instance of the class.
     */
    public function setJson(string $json): static
    {
        $this->json = $json;
        
        return $this;
    }
}

VarDumper::dump(Request::capture());
