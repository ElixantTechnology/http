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
namespace Elixant\HTTP\Concerns;

use stdClass;
use SplFileInfo;
use Elixant\Utility\Arr;
use Elixant\Utility\Carbon;
use Elixant\HTTP\UploadedFile;
use Elixant\Utility\Stringable;
use Illuminate\Support\Collection;
use Elixant\Utility\Traits\DumpableTrait;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * InteractsWithInput Trait
 *
 * @package         Elixant\HTTP\Concerns::InteractsWithInput
 * @class           InteractsWithInput
 * @version         GitHub: $Id$
 * @copyright       2024 (c) Elixant Corporation.
 * @license         MIT License
 * @author          Alexander M. Schmautz <a.schmautz91@gmail.com>
 * @since           Apr 05, 2024
 *
 * @mixin           \Elixant\HTTP\Request
 */
trait InteractsWithInput
{
    use DumpableTrait;
    
    /**
     * Retrieve a server variable from the request.
     *
     * @param string|null       $key
     * @param array|string|null $default
     *
     * @return string|array|null
     */
    public function server(string $key = null, array|string $default = null): array|string|null
    {
        return $this->retrieveItem('server', $key, $default);
    }
    
    /**
     * Retrieve a parameter item from a given source.
     *
     * @param string            $source
     * @param string|null       $key
     * @param array|string|null $default
     *
     * @return string|array|null
     */
    protected function retrieveItem(string $source, ?string $key, array|string|null $default): array|string|null
    {
        if (is_null($key)) {
            return $this->$source->all();
        }
        if ($this->$source instanceof InputBag) {
            return $this->$source->all()[$key] ?? $default;
        }
        
        return $this->$source->get($key, $default);
    }
    
    /**
     * Get all the input and files for the request.
     *
     * @param mixed|null $keys
     *
     * @return array
     */
    public function all(mixed $keys = null): array
    {
        $input = array_replace_recursive($this->input(), $this->allFiles());
        if ( ! $keys) {
            return $input;
        }
        $results = [];
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }
        
        return $results;
    }
    
    /**
     * Retrieve an input item from the request.
     *
     * @param string|null $key
     * @param mixed|null  $default
     *
     * @return mixed
     */
    public function input(string $key = null, mixed $default = null): mixed
    {
        return data_get(
            $this->getInputSource()->all() + $this->query->all(), $key, $default
        );
    }
    
    /**
     * Get an array of all the files on the request.
     *
     * @return array
     */
    public function allFiles(): array
    {
        $files = $this->files->all();
        
        return $this->convertedFiles = $this->convertedFiles ??
                                       $this->convertUploadedFiles($files);
    }
    
    /**
     * Convert the given array of Symfony UploadedFiles to custom Laravel
     * UploadedFiles.
     *
     * @param array $files
     *
     * @return array
     */
    protected function convertUploadedFiles(array $files): array
    {
        return array_map(function ($file) {
            if (is_null($file)
                || (is_array($file)
                    && empty(
                    array_filter(
                        $file
                    )
                    ))
            ) {
                return $file;
            }
            
            return is_array($file)
                ? $this->convertUploadedFiles($file)
                : UploadedFile::createFromBase($file);
        }, $files);
    }
    
    /**
     * @param string|array|null $key
     * @param mixed|null        $default
     *
     * @return mixed|null
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query($key, $default);
    }
    
    /**
     * Determine if a header is set on the request.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasHeader(string $key): bool
    {
        return ! is_null($this->header($key));
    }
    
    /**
     * Retrieve a header from the request.
     *
     * @param string|null       $key
     * @param array|string|null $default
     *
     * @return string|array|null
     */
    public function header(string $key = null, array|string $default = null): array|string|null
    {
        return $this->retrieveItem('headers', $key, $default);
    }
    
    /**
     * Get the bearer token from the request headers.
     *
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        $position = strrpos($header, 'Bearer ');
        if ($position !== false) {
            $header = substr($header, $position + 7);
            
            return str_contains($header, ',') ? strstr($header, ',', true)
                : $header;
        }
        
        return null;
    }
    
    /**
     * Determine if the request contains a given input item key.
     *
     * @param array|string $key
     *
     * @return bool
     */
    public function exists(array|string $key): bool
    {
        return $this->has($key);
    }
    
    /**
     * Determine if the request contains a given input item key.
     *
     * @param array|string $key
     *
     * @return bool
     */
    public function has(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        $input = $this->all();
        foreach ($keys as $value) {
            if ( ! Arr::has($input, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Determine if the request contains any of the given inputs.
     *
     * @param array|string $keys
     *
     * @return bool
     */
    public function hasAny(array|string $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = $this->all();
        
        return Arr::hasAny($input, $keys);
    }
    
    /**
     * Apply the callback if the request contains the given input item key.
     *
     * @param string        $key
     * @param callable      $callback
     * @param callable|null $default
     *
     * @return $this
     */
    public function whenHas(string $key, callable $callback, callable $default = null): static
    {
        if ($this->has($key)) {
            return $callback(data_get($this->all(), $key)) ? : $this;
        }
        if ($default) {
            return $default();
        }
        
        return $this;
    }
    
    /**
     * Determine if the request contains a non-empty value for any of the given
     * inputs.
     *
     * @param array|string $keys
     *
     * @return bool
     */
    public function anyFilled(array|string $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Determine if the request contains a non-empty value for an input item.
     *
     * @param array|string $key
     *
     * @return bool
     */
    public function filled(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Determine if the given input key is an empty string for "filled".
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isEmptyString(string $key): bool
    {
        $value = $this->input($key);
        
        return ! is_bool($value) && ! is_array($value)
               && trim((string)$value) === '';
    }
    
    /**
     * Apply the callback if the request contains a non-empty value for the
     * given input item key.
     *
     * @param string        $key
     * @param callable      $callback
     * @param callable|null $default
     *
     * @return $this
     */
    public function whenFilled(
        string $key, callable $callback, callable $default = null
    ): static {
        if ($this->filled($key)) {
            return $callback(data_get($this->all(), $key)) ? : $this;
        }
        if ($default) {
            return $default();
        }
        
        return $this;
    }
    
    /**
     * Apply the callback if the request is missing the given input item key.
     *
     * @param string        $key
     * @param callable      $callback
     * @param callable|null $default
     *
     * @return $this|mixed
     */
    public function whenMissing(
        string $key, callable $callback, callable $default = null
    ): mixed {
        if ($this->missing($key)) {
            return $callback(data_get($this->all(), $key)) ? : $this;
        }
        if ($default) {
            return $default();
        }
        
        return $this;
    }
    
    /**
     * Determine if the request is missing a given input item key.
     *
     * @param array|string $key
     *
     * @return bool
     */
    public function missing(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        
        return ! $this->has($keys);
    }
    
    /**
     * Get the keys for all the input and files.
     *
     * @return array
     */
    public function keys(): array
    {
        return array_merge(array_keys($this->input()), $this->files->keys());
    }
    
    /**
     * Retrieve input from the request as a Stringable instance.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return \Elixant\Utility\Stringable
     */
    public function str(string $key, mixed $default = null): Stringable
    {
        return $this->string($key, $default);
    }
    
    /**
     * Retrieve input from the request as a Stringable instance.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return \Elixant\Utility\Stringable
     */
    public function string(string $key, mixed $default = null): Stringable
    {
        return str($this->input($key, $default));
    }
    
    /**
     * Retrieve input as a boolean value.
     *
     * Returns true when value is "1", "true", "on", and "yes". Otherwise,
     * returns false.
     *
     * @param string|null $key
     * @param bool        $default
     *
     * @return bool
     */
    public function boolean(string $key = null, bool $default = false): bool
    {
        return filter_var(
            $this->input($key, $default), FILTER_VALIDATE_BOOLEAN
        );
    }
    
    /**
     * Retrieve input as an integer value.
     *
     * @param string $key
     * @param int    $default
     *
     * @return int
     */
    public function integer(string $key, int $default = 0): int
    {
        return intval($this->input($key, $default));
    }
    
    /**
     * Retrieve input as a float value.
     *
     * @param string $key
     * @param float  $default
     *
     * @return float
     */
    public function float(string $key, float $default = 0.0): float
    {
        return floatval($this->input($key, $default));
    }
    
    /**
     * Retrieve input from the request as a Carbon instance.
     *
     * @param string      $key
     * @param string|null $format
     * @param string|null $tz
     *
     * @return \Elixant\Utility\Carbon|false|null
     *
     * @throws \Carbon\Exceptions\InvalidFormatException
     */
    public function date(string $key, string $format = null, string $tz = null): Carbon|false|null
    {
        if ($this->isNotFilled($key)) {
            return null;
        }
        if (is_null($format)) {
            return (new Carbon())->parse($this->input($key), $tz);
        }
        
        return (new Carbon())->createFromFormat($format, $this->input($key), $tz);
    }
    
    /**
     * Determine if the request contains an empty value for an input item.
     *
     * @param array|string $key
     *
     * @return bool
     */
    public function isNotFilled(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if ( ! $this->isEmptyString($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Retrieve input from the request as an enum.
     *
     * @template TEnum
     *
     * @param string              $key
     * @param class-string<TEnum> $enumClass
     *
     * @return TEnum|null
     */
    public function enum(string $key, string $enumClass)
    {
        if ($this->isNotFilled($key)
            ||
            ! enum_exists($enumClass)
            ||
            ! method_exists($enumClass, 'tryFrom')
        ) {
            return null;
        }
        
        return $enumClass::tryFrom($this->input($key));
    }
    
    /**
     * Retrieve input from the request as a collection.
     *
     * @param array|string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function collect(array|string $key = null): Collection
    {
        return collect(is_array($key) ? $this->only($key) : $this->input($key));
    }
    
    /**
     * Get a subset containing the provided keys with values from the input
     * data.
     *
     * @param array|mixed $keys
     *
     * @return array
     */
    public function only(mixed $keys): array
    {
        $results = [];
        $input = $this->all();
        $placeholder = new stdClass;
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = data_get($input, $key, $placeholder);
            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }
        
        return $results;
    }
    
    /**
     * Get all the input except for a specified array of items.
     *
     * @param array|mixed $keys
     *
     * @return array
     */
    public function except(mixed $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = $this->all();
        Arr::forget($results, $keys);
        
        return $results;
    }
    
    public function query(string|array|null $key = null, mixed $default = null)
    {
        return $this[$key] ?? $default;
    }
    
    public function attribute(
        string|array|null $key = null, mixed $default = null
    ) {
        return $this[$key] ?? $default;
    }
    
    /**
     * Retrieve a request payload item from the request.
     *
     * @param string|null       $key
     * @param array|string|null $default
     *
     * @return string|array|null
     */
    public function post(string $key = null, array|string $default = null): array|string|null
    {
        return $this->retrieveItem('request', $key, $default);
    }
    
    /**
     * Determine if a cookie is set on the request.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasCookie(string $key): bool
    {
        return ! is_null($this->cookie($key));
    }
    
    /**
     * Retrieve a cookie from the request.
     *
     * @param string|null       $key
     * @param array|string|null $default
     *
     * @return string|array|null
     */
    public function cookie(string $key = null, array|string $default = null): array|string|null
    {
        return $this->retrieveItem('cookies', $key, $default);
    }
    
    /**
     * Determine if the uploaded data contains a file.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        if ( ! is_array($files = $this->file($key))) {
            $files = [$files];
        }
        foreach ($files as $file) {
            if ($this->isValidFile($file)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Retrieve a file from the request.
     *
     * @param string|null $key
     * @param mixed|null  $default
     *
     * @return \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|array|null
     */
    public function file(string $key = null, mixed $default = null): array|\Illuminate\Http\UploadedFile|null
    {
        return data_get($this->allFiles(), $key, $default);
    }
    
    /**
     * Check that the given file is a valid file instance.
     *
     * @param mixed $file
     *
     * @return bool
     */
    protected function isValidFile(mixed $file): bool
    {
        return $file instanceof SplFileInfo && $file->getPath() !== '';
    }
    
    /**
     * Dump the items.
     *
     * @param mixed $keys
     *
     * @return $this
     */
    public function dump($keys = []): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        dump(count($keys) > 0 ? $this->only($keys) : $this->all());
        
        return $this;
    }
}
