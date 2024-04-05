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

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * UploadedFile Class.
 *
 * @package         Elixant\HTTP::UploadedFile
 * @class           UploadedFile
 * @version         GitHub: $Id$
 * @copyright       2024 (c) Elixant Corporation.
 * @license         MIT License
 * @author          Alexander M. Schmautz <a.schmautz91@gmail.com>
 * @since           Apr 05, 2024
 */
class UploadedFile extends SymfonyUploadedFile
{
    use FileHelpers;
    
    /**
     * Store the uploaded file on a filesystem disk.
     *
     * @param string $path
     *
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function store(string $path = ''): File
    {
        return $this->storeAs($path, $this->hashName());
    }
    
    /**
     * Store the uploaded file on a filesystem disk with public visibility.
     *
     * @param string $path
     *
     * @return false|\Symfony\Component\HttpFoundation\File\File|string
     */
    public function storePublicly(string $path = ''): false|File|string
    {
        return $this->storeAs($path, $this->hashName());
    }
    
    /**
     * Store the uploaded file on a filesystem disk with public visibility.
     *
     * @param string      $path
     * @param string|null $name
     *
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function storePubliclyAs(string $path, string $name = null): File
    {
        return $this->storeAs($path, $name);
    }
    
    /**
     * Store the uploaded file on a filesystem disk.
     *
     * @param string            $path
     * @param array|string|null $name
     *
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function storeAs(string $path, array|string $name = null): File
    {
        if (is_null($name) || is_array($name)) {
            [$path, $name] = ['', $path, $name ?? []];
        }
        
        return $this->move($path, $name);
    }
    
    /**
     * Parse and format the given options.
     *
     * @param array|string $options
     *
     * @return array|string
     */
    protected function parseOptions(array|string $options): array|string
    {
        if (is_string($options)) {
            $options = ['disk' => $options];
        }
        
        return $options;
    }
    
    /**
     * Create a new file instance from a base instance.
     *
     * @param  \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param bool                                                 $test
     *
     * @return static
     *
     */
    public static function createFromBase(SymfonyUploadedFile $file, bool $test = false): static
    {
        return $file instanceof static ? $file : new static(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getError(),
            $test
        );
    }
}