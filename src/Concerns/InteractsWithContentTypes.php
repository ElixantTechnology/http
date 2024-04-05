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

use Elixant\Utility\Str;

/**
 * Trait InteractsWithContentTypes
 *
 * @package         Elixant\HTTP\Concerns::InteractsWithContentTypes
 * @trait           InteractsWithContentTypes
 * @version         GitHub: $Id$
 * @copyright       2024 (c) Elixant Corporation.
 * @license         MIT License
 * @author          Alexander M. Schmautz <a.schmautz91@gmail.com>
 * @since           Apr 05, 2024
 *
 * @mixin \Elixant\HTTP\Request
 */
trait InteractsWithContentTypes
{
    /**
     * Determine if the request is sending JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return Str::contains(
            $this->header('CONTENT_TYPE') ?? '', ['/json', '+json']
        );
    }
    
    /**
     * Determine if the current request probably expects a JSON response.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        return ($this->ajax() && ! $this->pjax()
                && $this->acceptsAnyContentType())
               || $this->wantsJson();
    }
    
    /**
     * Determine if the current request accepts any content type.
     *
     * @return bool
     */
    public function acceptsAnyContentType(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();
        
        return count($acceptable) === 0
               || (
                   isset($acceptable[0])
                   && ($acceptable[0] === '*/*'
                       || $acceptable[0] === '*')
               );
    }
    
    /**
     * Determine if the current request is asking for JSON.
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();
        
        return isset($acceptable[0])
               && Str::contains(
                strtolower($acceptable[0]), ['/json', '+json']
            );
    }
    
    /**
     * Return the most suitable content type from the given array based on
     * content negotiation.
     *
     * @param array|string $contentTypes
     *
     * @return string|null
     */
    public function prefers(array|string $contentTypes): ?string
    {
        $accepts      = $this->getAcceptableContentTypes();
        $contentTypes = (array)$contentTypes;
        foreach ($accepts as $accept) {
            if (in_array($accept, ['*/*', '*'])) {
                return $contentTypes[0];
            }
            foreach ($contentTypes as $contentType) {
                $type = $contentType;
                if ( ! is_null($mimeType = $this->getMimeType($contentType))) {
                    $type = $mimeType;
                }
                $accept = strtolower($accept);
                $type   = strtolower($type);
                if ($this->matchesType($type, $accept)
                    || $accept === strtok(
                                       $type, '/'
                                   ) . '/*'
                ) {
                    return $contentType;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Determine if the given content types match.
     *
     * @param string $actual
     * @param string $type
     *
     * @return bool
     */
    public static function matchesType(string $actual, string $type): bool
    {
        if ($actual === $type) {
            return true;
        }
        $split = explode('/', $actual);
        
        return isset($split[1])
               && preg_match(
                   '#' . preg_quote($split[0], '#') . '/.+\+' . preg_quote(
                       $split[1], '#'
                   ) . '#', $type
               );
    }
    
    /**
     * Determines whether a request accepts JSON.
     *
     * @return bool
     */
    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }
    
    /**
     * Determines whether the current requests accept a given content type.
     *
     * @param array|string $contentTypes
     *
     * @return bool
     */
    public function accepts(array|string $contentTypes): bool
    {
        $accepts = $this->getAcceptableContentTypes();
        if (count($accepts) === 0) {
            return true;
        }
        $types = (array)$contentTypes;
        foreach ($accepts as $accept) {
            if ($accept === '*/*' || $accept === '*') {
                return true;
            }
            foreach ($types as $type) {
                $accept = strtolower($accept);
                $type   = strtolower($type);
                if ($this->matchesType($accept, $type)
                    || $accept === strtok(
                                       $type, '/'
                                   ) . '/*'
                ) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Determines whether a request accepts HTML.
     *
     * @return bool
     */
    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }
    
    /**
     * Get the data format expected in the response.
     *
     * @param string $default
     *
     * @return string
     */
    public function format(string $default = 'html'): string
    {
        foreach ($this->getAcceptableContentTypes() as $type) {
            if ($format = $this->getFormat($type)) {
                return $format;
            }
        }
        
        return $default;
    }
}