<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services;

use Illuminate\Support\Facades\File;

class InstallService
{
    private const REQUIRED_DIRECTORIES = [
        'resources/api-docs',
    ];

    public function createDirectories(): void
    {
        foreach (self::REQUIRED_DIRECTORIES as $directory) {
            $path = base_path($directory);

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    public function isInstalled(): bool
    {
        return File::exists(config_path('api-contract.php'));
    }

    public function installedVersion(): string
    {
        return '1.0.0';
    }

    /** @return array<string, string> */
    public function requirements(): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
        ];
    }
}
