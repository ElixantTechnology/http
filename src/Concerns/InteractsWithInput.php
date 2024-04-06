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
namespace Elixant\HTTP\Concerns;

use stdClass;
use Elixant\Utility\Arr;
use Elixant\HTTP\Request;
use Elixant\HTTP\Response;
use Elixant\Utility\Carbon;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Trait InteractsWithInput
 *
 * The InteractsWithInput trait provides methods for interacting with user input data.
 *
 * @package         Elixant/HTTP
 * @copyright       2024 (c) Elixant Corporation.
 * @license         MIT License
 * @author          Alexander M. Schmautz <a.schmautz91@gmail.com>
 * @trait
 */
trait InteractsWithInput
{
    /**
     * Retrieves an item from the specified source using the given key and returns it.
     * If the key is null, the entire source will be returned.
     * If the source is an instance of InputBag, the value for the specified key will be returned,
     * or the default value if the key is not found.
     *
     * @param string            $source  The source to retrieve the item from.
     * @param string|null       $key     The key of the item to retrieve. Null to retrieve the entire source.
     * @param string|array|null $default (optional) The default value to return if the key is not found. Default is null.
     *
     * @return string|array|null The retrieved item from the source. Null if the key is not found and no default value is provided.
     */
    protected function retrieveItem(string $source, ?string $key, string|array|null $default): string|array|null
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
     * Retrieves an item from the server source using the given key and returns it.
     * If the key is null, the entire server source will be returned.
     * If the key is not found in the server source, it will return the default value,
     * or null if no default value is provided.
     *
     * @param string|null       $key     (optional) The key of the item to retrieve. Null to retrieve the entire server source.
     * @param string|array|null $default (optional) The default value to return if the key is not found. Default is null.
     *
     * @return string|array|null The retrieved item from the server source. Null if the key is not found and no default value is provided.
     */
    public function server(?string $key = null, string|array|null $default = null): string|array|null
    {
        return $this->retrieveItem('server', $key, $default);
    }
    
    /**
     * Retrieves a cookie value from the request using the given key and returns it.
     * If the key is null, the entire set of cookies will be returned.
     * If the cookie with the specified key is not found, the default value will be returned.
     *
     * @param string|null       $key     (optional) The key of the cookie to retrieve. Null to retrieve all cookies. Default is null.
     * @param array|string|null $default (optional) The default value to return if the cookie is not found. Default is null.
     *
     * @return string|array|null The value of the cookie with the specified key. Null if the cookie is not found and no default value is provided.
     */
    public function cookie(?string $key = null, null|array|string $default = null): string|array|null
    {
        return $this->retrieveItem('cookies', $key, $default);
    }
    
    /**
     * Checks if the specified cookie exists.
     *
     * @param string $key The key of the cookie to check.
     *
     * @return bool True if the cookie exists, false otherwise.
     */
    public function hasCookie(string $key): bool
    {
        return ! is_null($this->cookie($key));
    }
    
    /**
     * Retrieves an item from the 'request' source using the given key and returns it.
     * If the key is null, the entire 'request' source will be returned.
     *
     * @param string|null       $key     The key of the item to retrieve. Null to retrieve the entire 'request' source.
     * @param array|string|null $default (optional) The default value to return if the key is not found. Default is null.
     *
     * @return string|array|null The retrieved item from the 'request' source. Null if the key is not found and no default value is provided.
     */
    public function post(?string $key = null, null|array|string $default = null): string|array|null
    {
        return $this->retrieveItem('request', $key, $default);
    }
    
    /**
     * Retrieve the value of a query parameter.
     *
     * @param mixed|null $key     The name of the query parameter. Defaults to null.
     * @param mixed|null $default The default value to return if the query parameter is not found. Defaults to null.
     *
     * @return string|array|null The value of the query parameter as a string, an array if multiple values exist, or null if not found
     */
    public function query(string|array|null $key = null, string|array|null $default = null): string|array|null
    {
        return $this->retrieveItem('query', $key, $default);
    }
    
    /**
     * Remove the specified keys from the array.
     *
     * @param string|array $keys The keys to be removed from the array. Can be either a single string or an array of strings.
     *
     * @return array The array with the specified keys removed.
     */
    public function except(string|array $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        
        $results = $this->all();
        
        Arr::forget($results, $keys);
        
        return $results;
    }
    
    /**
     * Get a subset of the input data, with only the specified keys.
     *
     * @param array|string $keys The keys to include in the resulting array.
     *
     * @return array The subset of input data with only the specified keys.
     */
    public function only(array|string $keys): array
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
     * Retrieve the value of an enumerated parameter.
     *
     * @param string        $key       The name of the enumerated parameter.
     * @param string|object $enumClass The class name or object of the enumeration.
     *
     * @return string|null The value of the enumerated parameter as a string, or null if not found or not valid.
     */
    public function enum(string $key, string|object $enumClass): ?string
    {
        if ($this->isNotFilled($key) ||
            ! enum_exists($enumClass) ||
            ! method_exists($enumClass, 'tryFrom')) {
            return null;
        }
        
        return $enumClass::tryFrom($this->input($key));
    }
    
    /**
     * Retrieve the value of a date parameter and convert it to a Carbon object.
     *
     * @param string      $key    The name of the date parameter.
     * @param string|null $format The format to parse the date parameter. Defaults to null.
     * @param string|null $tz     The timezone for the date parameter. Defaults to null.
     *
     * @return Carbon|null         A Carbon object representing the date parameter value if it exists and can be parsed in the given format. Returns null if the date parameter is not found
     * or cannot be parsed.
     */
    public function date(string $key, string $format = null, string $tz = null): ?Carbon
    {
        if ($this->isNotFilled($key)) {
            return null;
        }
        
        if (is_null($format)) {
            return Carbon::parse($this->input($key), $tz);
        }
        
        return Carbon::createFromFormat($format, $this->input($key), $tz);
    }
    
    /**
     * Converts the value of a input parameter to a float.
     *
     * @param string                  $key     The name of the input parameter.
     * @param array|string|float|null $default The default value to return if the input parameter is not found. Defaults to 0.0.
     *
     * @return float The value of the input parameter as a float.
     */
    public function float(string $key, array|string|null|float $default = 0.0): float
    {
        return floatval($this->input($key, $default));
    }
    
    /**
     * Convert the value of a given input parameter to an integer.
     * If the input parameter is not found, the default value will be used.
     *
     * @param string                $key     The name of the input parameter.
     * @param array|string|int|null $default The default value to use if the input parameter is not found. Defaults to 0.
     *
     * @return int The value of the input parameter as an integer.
     */
    public function integer(string $key, array|string|null|int $default = 0): int
    {
        return intval($this->input($key, $default));
    }
    
    /**
     * Retrieve the value of a boolean parameter.
     *
     * @param string|null $key     The name of the boolean parameter. Defaults to null.
     * @param bool        $default The default value to return if the boolean parameter is not found. Defaults to false.
     *
     * @return bool The value of the boolean parameter. Returns true if the parameter is "true", "1", "on", or "yes". Returns false if the parameter is "false", "0", "off", or "no", or if
     * the parameter is not found.
     */
    public function boolean(string $key = null, array|string|null|bool $default = false): bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Get a string value from the input using the specified key.
     *
     * @param mixed $key     The key of the value to retrieve from the input.
     * @param mixed $default The default value to return if the key is not found. Defaults to null.
     *
     * @return string The string value from the input, or the default value if the key is not found.
     */
    public function string(string $key, bool $default = null): string
    {
        return str($this->input($key, $default));
    }
    
    /**
     * Retrieve the value of a string parameter.
     *
     * @param string     $key     The name of the string parameter.
     * @param mixed|null $default The default value to return if the string parameter is not found. Defaults to null.
     *
     * @return string The value of the string parameter as a string or the default value if not found.
     */
    public function str(string $key, null $default = null): string
    {
        return $this->string($key, $default);
    }
    
    /**
     * Retrieve the value of an input parameter.
     *
     * @param string|null       $key     The name of the input parameter. Defaults to null.
     * @param array|string|null $default The default value to return if the input parameter is not found. Defaults to null.
     *
     * @return array|string|null    The value of the input parameter as an array if multiple values exist, a string if only one value exists, or null if not found
     */
    public function input(string $key = null, array|string|null $default = null): array|string|null
    {
        return data_get(
            $this->getInputSource()->all() + $this->query->all(), $key, $default
        );
    }
    
    /**
     * Retrieve all input data or specified input data keys.
     *
     * @param array|string|null $keys     The keys of the input data to retrieve. Defaults to null.
     *                                    If null, retrieves all input data.
     *                                    If an array, retrieves the specified input data keys.
     *                                    If a string, retrieves the input data with the specified key.
     *
     * @return array The retrieved input data as an associative array.
     *               If no keys are specified or $keys is null, returns all input data.
     *               If $keys is an array, returns the input data with the specified keys.
     *               If $keys is a string, returns the input data with the specified key.
     */
    public function all(array|string|null $keys = null): array
    {
        $input = $this->input();
        
        if (! $keys) {
            return $input;
        }
        
        $results = [];
        
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }
        
        return $results;
    }
    
    /**
     * Check if a string is empty.
     *
     * @param string $key The string to check for emptiness.
     *
     * @return bool Returns true if the string is empty, false otherwise.
     */
    protected function isEmptyString(string $key): bool
    {
        $value = $this->input($key);
        
        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }
    
    /**
     * Execute a callback when a key is missing.
     *
     * @param string        $key      The name of the key to check for.
     * @param callable      $callback The callback function to execute if the key is missing.
     * @param callable|null $default  (Optional) The callback function to execute if the key is missing and no callback is provided.
     *
     * @return \Elixant\HTTP\Request|\Elixant\HTTP\Response The current object instance.
     */
    public function whenMissing(string $key, callable $callback, ?callable $default = null): Request|Response
    {
        if ($this->missing($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }
        
        if ($default) {
            return $default();
        }
        
        return $this;
    }
    
    /**
     * Check if one or more keys are missing.
     *
     * @param array|string $key The key(s) to check for existence.
     *
     * @return bool Returns true if any of the keys are missing, false otherwise.
     */
    public function missing(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        
        return ! $this->has($keys);
    }
    
    /**
     * @param               $key
     * @param callable      $callback
     * @param callable|null $default
     *
     * @return \Elixant\HTTP\Response|\Elixant\HTTP\Request
     */
    public function whenFilled($key, callable $callback, ?callable $default = null): Request|Response
    {
        if ($this->filled($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }
        
        if ($default) {
            return $default();
        }
        
        return $this;
    }
    
    /**
     * Check if the given value or values are not filled.
     *
     * @param mixed ...$values The value or values to check if they are not filled.
     *
     * @return bool True if the value or values are not filled, false otherwise.
     */
    public function isNotFilled(array|string ...$values): bool
    {
        $keys = is_array($values) ? $values : func_get_args();
        
        foreach ($keys as $value) {
            if (! $this->isEmptyString($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Determine if the given value is filled.
     *
     * @param array|string ...$key The value(s) to check if they are filled.
     *
     * @return bool Returns true if the value(s) are filled, false otherwise.
     */
    public function filled(array|string ...$key): bool
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
     * Executes a callback function if a given key exists in the data.
     *
     * @param mixed         $key      The key to check for existence in the data.
     * @param callable      $callback The function to execute if the key exists in the data.
     * @param callable|null $default  The function to execute if the key does not exist in the data. Defaults to null.
     *
     * @return \Elixant\HTTP\Request|\Elixant\HTTP\Response Returns the current instance of the class.
     */
    public function whenHas(string $key, callable $callback, ?callable $default = null): Request|Response
    {
        if ($this->has($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }
        
        if ($default) {
            return $default();
        }
        
        return $this;
    }
    
    /**
     * Check if any of the given keys exist in the input array.
     *
     * @param array|string ...$keys The keys to check for. Can be passed as indivi`du`al arguments or as an array.
     *
     * @return bool True if any of the keys exist in the input array, false otherwise.
     */
    public function hasAny(array|string ...$keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        
        $input = $this->all();
        
        return Arr::hasAny($input, $keys);
    }
    
    /**
     * Determine if the given key exists in the input data.
     *
     * @param array|string $key The key(s) to check for existence in the input data.
     *
     * @return bool True if the key(s) exist in the input data, false otherwise.
     */
    public function has(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        
        $input = $this->all();
        
        foreach ($keys as $value) {
            if (! Arr::has($input, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if a specific key exists.
     *
     * @param string $key The name of the key to check for existence.
     *
     * @return bool True if the key exists, false otherwise.
     */
    public function exists(string $key): bool
    {
        return $this->has($key);
    }
    
    public function dump(?array ...$keys): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        
        dump(count($keys) > 0 ? $this->only($keys) : $this->all());
        
        return $this;
    }
}
