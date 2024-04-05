<?php
/**
 * Elixant Platform Framework Component
 *
 * Elixant Platform
 * Copyright (c) 2023 Elixant Corporation.
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
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
 * USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @copyright    2023 (C) Elixant Corporation.
 * @license      MIT License
 * @author       Alexander Schmautz <a.schmautz@outlook.com>
 */
declare(strict_types = 1);
namespace Elixant\HTTP;

use ArrayAccess;
use Elixant\Utility\Str;
use Elixant\Utility\Arr;
use Elixant\HTTP\Concerns\InteractsWithInput;
use Symfony\Component\HttpFoundation\InputBag;
use Elixant\HTTP\Concerns\InteractsWithContentTypes;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Representation of an incoming, server-side HTTP request.
 *
 * @package         Elixant\HTTP\Request
 * @class           Request
 * @version         GitHub: $Id$
 * @copyright       2024 (c) Elixant Corporation.
 * @license         MIT License
 * @author          Alexander M. Schmautz <a.schmautz91@gmail.com>
 * @since           Apr 05, 2024
 *
 * @method array validate(array $rules, ...$params)
 * @method array validateWithBag(string $errorBag, array $rules, ...$params)
 * @method bool hasValidSignature(bool $absolute = true)
 */
class Request extends SymfonyRequest implements ArrayAccess
{
    use InteractsWithInput,
        InteractsWithContentTypes;
    protected ?InputBag $json;
    protected array $convertedFiles = [];
    
    /**
     * Return the Request instance.
     *
     * @return $this
     */
    public function instance(): static
    {
        return $this;
    }
    
    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->getMethod();
    }
    
    /**
     * Get the root URL for the application.
     *
     * @return string
     */
    public function root(): string
    {
        return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(), '/');
    }
    
    /**
     * Get the URL (no query string) for the request.
     *
     * @return string
     */
    public function url(): string
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }
    
    /**
     * Get the full URL for the request.
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $query = $this->getQueryString();
        
        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';
        
        return $query ? $this->url().$question.$query : $this->url();
    }
    
    /**
     * Get the full URL for the request with the added query string parameters.
     *
     * @param  array  $query
     * @return string
     */
    public function fullUrlWithQuery(array $query): string
    {
        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';
        
        return count($this->query()) > 0
            ? $this->url().$question.Arr::query(array_merge($this->query(), $query))
            : $this->fullUrl().$question.Arr::query($query);
    }
    
    /**
     * Get the full URL for the request without the given query string parameters.
     *
     * @param array|string $keys
     *
     * @return string
     */
    public function fullUrlWithoutQuery(array|string $keys): string
    {
        $query = Arr::except($this->query(), $keys);
        
        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';
        
        return count($query) > 0
            ? $this->url().$question.Arr::query($query)
            : $this->url();
    }
    
    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path(): string
    {
        $pattern = trim($this->getPathInfo(), '/');
        
        return $pattern === '' ? '/' : $pattern;
    }
    
    /**
     * Get the current decoded path info for the request.
     *
     * @return string
     */
    public function decodedPath(): string
    {
        return rawurldecode($this->path());
    }
    
    /**
     * Get a segment from the URI (1 based index).
     *
     * @param int         $index
     * @param string|null $default
     *
     * @return string|null
     */
    public function segment(int $index, string $default = null): ?string
    {
        return Arr::get($this->segments(), $index - 1, $default);
    }
    
    /**
     * Get all the segments for the request path.
     *
     * @return array
     */
    public function segments(): array
    {
        $segments = explode('/', $this->decodedPath());
        
        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }
    
    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function is(...$patterns): bool
    {
        $path = $this->decodedPath();
        
        return collect($patterns)->contains(fn ($pattern) => Str::is($pattern, $path));
    }
    
    /**
     * Determine if the current request URL and query string match a pattern.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function fullUrlIs(...$patterns): bool
    {
        $url = $this->fullUrl();
        
        return collect($patterns)->contains(fn ($pattern) => Str::is($pattern, $url));
    }
    
    /**
     * Get the host name.
     *
     * @return string
     */
    public function host(): string
    {
        return $this->getHost();
    }
    
    /**
     * Get the HTTP host being requested.
     *
     * @return string
     */
    public function httpHost(): string
    {
        return $this->getHttpHost();
    }
    
    /**
     * Get the scheme and HTTP host.
     *
     * @return string
     */
    public function schemeAndHttpHost(): string
    {
        return $this->getSchemeAndHttpHost();
    }
    
    /**
     * Determine if the request is the result of an AJAX call.
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return $this->isXmlHttpRequest();
    }
    
    /**
     * Determine if the request is the result of a PJAX call.
     *
     * @return bool
     */
    public function pjax(): bool
    {
        return $this->headers->get('X-PJAX') == true;
    }
    
    /**
     * Determine if the request is the result of a prefetch call.
     *
     * @return bool
     */
    public function prefetch(): bool
    {
        return strcasecmp($this->server->get('HTTP_X_MOZ') ?? '', 'prefetch') === 0 ||
               strcasecmp($this->headers->get('Purpose') ?? '', 'prefetch') === 0 ||
               strcasecmp($this->headers->get('Sec-Purpose') ?? '', 'prefetch') === 0;
    }
    
    /**
     * Determine if the request is over HTTPS.
     *
     * @return bool
     */
    public function secure(): bool
    {
        return $this->isSecure();
    }
    
    /**
     * Get the client user agent.
     *
     * @return string|null
     */
    public function userAgent(): ?string
    {
        return $this->headers->get('User-Agent');
    }
    
    /**
     * Merge new input into the current request's input array.
     *
     * @param  array  $input
     * @return $this
     */
    public function merge(array $input): static
    {
        $this->getInputSource()->add($input);
        
        return $this;
    }
    
    /**
     * Merge new input into the request's input, but only when that key is missing from the request.
     *
     * @param  array  $input
     * @return $this
     */
    public function mergeIfMissing(array $input): static
    {
        return $this->merge(collect($input)->filter(
            fn($value, $key) => $this->missing($key)
        )->toArray());
    }
    
    /**
     * Replace the input for the current request.
     *
     * @param  array  $input
     * @return $this
     */
    public function replace(array $input): static
    {
        $this->getInputSource()->replace($input);
        
        return $this;
    }
    
    public function json($key = null, $default = null)
    {
        if (! isset($this->json)) {
            $this->json = new InputBag((array) json_decode($this->getContent(), true));
        }
        
        if (is_null($key)) {
            return $this->json;
        }
        
        return data_get($this->json->all(), $key, $default);
    }
    
    protected function getInputSource()
    {
        if ($this->isJson()) {
            return $this->json();
        }
        
        return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }
    
    public static function createFrom(self $from, $to = null)
    {
        $request = $to ?: new static;
        
        $files = array_filter($from->files->all());
        
        $request->initialize(
            $from->query->all(),
            $from->request->all(),
            $from->attributes->all(),
            $from->cookies->all(),
            $files,
            $from->server->all(),
            $from->getContent()
        );
        
        $request->headers->replace($from->headers->all());
        
        $request->setRequestLocale($from->getLocale());
        
        $request->setDefaultRequestLocale($from->getDefaultLocale());
        
        $request->setJson($from->json());
        
        return $request;
    }
    
    /**
     * Create an Illuminate request from a Symfony instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return static
     */
    public static function createFromBase(SymfonyRequest $request): static
    {
        $newRequest = new static(
            $request->query->all(), $request->request->all(), $request->attributes->all(),
            $request->cookies->all(), (new static)->filterFiles($request->files->all()) ?? [], $request->server->all()
        );
        
        $newRequest->headers->replace($request->headers->all());
        
        $newRequest->content = $request->content;
        
        if ($newRequest->isJson()) {
            $newRequest->request = $newRequest->json();
        }
        
        return $newRequest;
    }
    
    /**
     * Filter the given array of files, removing any empty values.
     *
     * @param  mixed  $files
     * @return mixed
     */
    protected function filterFiles(mixed $files): array
    {
        if (! $files) {
            return [];
        }
        
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $files[$key] = $this->filterFiles($file);
            }
            
            if (empty($files[$key])) {
                unset($files[$key]);
            }
        }
        
        return $files;
    }
    
    /**
     * Set the locale for the request instance.
     *
     * @param  string  $locale
     * @return void
     */
    public function setRequestLocale(string $locale): void
    {
        $this->locale = $locale;
    }
    
    /**
     * Set the default locale for the request instance.
     *
     * @param  string  $locale
     * @return void
     */
    public function setDefaultRequestLocale(string $locale): void
    {
        $this->defaultLocale = $locale;
    }
    
    /**
     * Set the JSON payload for the request.
     *
     * @param  \Symfony\Component\HttpFoundation\InputBag  $json
     * @return $this
     */
    public function setJson($json): static
    {
        $this->json = $json;
        
        return $this;
    }
    
    /**
     * Get all the input and files for the request.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->all();
    }
    
    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return Arr::has(
            $this->all(),
            $offset
        );
    }
    
    /**
     * Get the value at the given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }
    
    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->getInputSource()->set($offset, $value);
    }
    
    /**
     * Remove the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->getInputSource()->remove($offset);
    }
    
    /**
     * Check if an input element is set on the request.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->__get($key));
    }
    
    /**
     * Get an input element from the request.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return Arr::get($this->all(), $key);
    }
}