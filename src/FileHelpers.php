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

use Elixant\Utility\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     *
     * @var ?string
     */
    protected ?string $hashName = null;
    
    /**
     * Get the fully qualified path to the file.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->getRealPath();
    }
    
    /**
     * Get the file's extension.
     *
     * @return string
     */
    public function extension(): string
    {
        return $this->guessExtension();
    }
    
    /**
     * Get a filename for the file.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function hashName(string $path = null): string
    {
        if ($path) {
            $path = rtrim($path, '/') . '/';
        }
        $hash = $this->hashName ? : $this->hashName = Str::random(40);
        if ($extension = $this->guessExtension()) {
            $extension = '.' . $extension;
        }
        
        return $path . $hash . $extension;
    }
    
    /**
     * Get the dimensions of the image (if applicable).
     *
     * @return array|null
     */
    public function dimensions(): ?array
    {
        return @getimagesize($this->getRealPath());
    }
}