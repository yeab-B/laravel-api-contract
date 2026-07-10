<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Config;

use Illuminate\Contracts\Config\Repository;

class Configuration
{
    public function __construct(
        private readonly Repository $config,
    ) {
    }

    /** @return array<string, string> */
    public function generators(): array
    {
        $value = $this->config->get('api-contract.generators', []);

        return is_array($value) ? $value : [];
    }

    public function frontendFramework(): string
    {
        $value = $this->config->get('api-contract.frontend_framework', 'react');

        return is_string($value) ? $value : 'react';
    }

    public function authenticationDriver(): string
    {
        $value = $this->config->get('api-contract.auth_driver', 'sanctum');

        return is_string($value) ? $value : 'sanctum';
    }

    public function outputPath(?string $generator = null): ?string
    {
        $key = $generator !== null
            ? "api-contract.paths.{$generator}"
            : 'api-contract.paths.output';

        $value = $this->config->get($key);

        return is_string($value) ? $value : null;
    }

    public function apiPrefix(): string
    {
        $value = $this->config->get('api-contract.api_prefix', 'api');

        return is_string($value) ? $value : 'api';
    }

    /** @return array<int, string> */
    public function scanPaths(): array
    {
        $value = $this->config->get('api-contract.paths.scan', []);

        return is_array($value) ? $value : [];
    }

    /** @return array<string, mixed> */
    public function generationOptions(): array
    {
        $value = $this->config->get('api-contract.generation', []);

        return is_array($value) ? $value : [];
    }

    public function contractName(): string
    {
        $value = $this->config->get('api-contract.contract.name', 'Laravel API');

        return is_string($value) ? $value : 'Laravel API';
    }

    public function contractVersion(): string
    {
        $value = $this->config->get('api-contract.contract.version', '1.0');

        return is_string($value) ? $value : '1.0';
    }

    /**
     * Validate a path to prevent path traversal outside the project base directory or system temporary directory.
     */
    public function ensureSafePath(string $path): void
    {
        $resolved = realpath(dirname($path));

        if ($resolved === false) {
            $resolved = $this->canonicalizePath(dirname($path));
        }

        $basePath = function_exists('base_path') ? base_path() : null;
        $tempPath = realpath(sys_get_temp_dir());

        $isUnderBase = $basePath && str_starts_with($resolved, $basePath);
        $isUnderTemp = $tempPath && str_starts_with($resolved, $tempPath);

        if (!$isUnderBase && !$isUnderTemp) {
            throw new \InvalidArgumentException(
                "Path traversal detected or path outside allowed directories: " . $path
            );
        }
    }

    private function canonicalizePath(string $path): string
    {
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), fn($p) => $p !== '');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }
            if ('..' === $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $prefix = DIRECTORY_SEPARATOR === '/' ? '/' : '';
        return $prefix . implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}
