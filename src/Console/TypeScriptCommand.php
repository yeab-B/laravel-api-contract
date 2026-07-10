<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Contracts\TypeScriptGeneratorContract;
use Yab\LaravelApiContract\Config\Configuration;

class TypeScriptCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    protected $signature = 'api-contract:typescript
                            {--output= : Path to write the generated TypeScript file}';

    protected $description = 'Generate TypeScript type definitions from the API contract';

    public function handle(
        ContractBuilderContract $builder,
        TypeScriptGeneratorContract $generator,
        Configuration $config,
    ): int {
        $this->components->info('Generating TypeScript definitions from API contract...');

        $contract = $builder->build();

        $files = $generator->generate($contract);

        if ($files === []) {
            $this->components->warn('No endpoints found; no TypeScript files generated.');

            return self::SUCCESS;
        }

        $outputPath = $this->option('output');

        if ($outputPath === null || $outputPath === false || is_array($outputPath)) {
            foreach ($files as $file) {
                $this->line("--- {$file['filename']} ---");
                $this->line($file['content']);
            }

            return self::SUCCESS;
        }

        $outputPath = (string) $outputPath;

        $config->ensureSafePath($outputPath);

        $directory = dirname($outputPath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                $this->components->error("Failed to create directory: {$directory}");

                return self::FAILURE;
            }
        }

        if (count($files) === 1) {
            $content = $files[0]['content'];

            if (file_put_contents($outputPath, $content) === false) {
                $this->components->error("Failed to write TypeScript file to: {$outputPath}");

                return self::FAILURE;
            }

            $this->components->info("TypeScript definitions written to: {$outputPath}");
        } else {
            $base = rtrim($outputPath, '/');

            if (!is_dir($base)) {
                if (!mkdir($base, 0755, true) && !is_dir($base)) {
                    $this->components->error("Failed to create directory: {$base}");

                    return self::FAILURE;
                }
            }

            foreach ($files as $file) {
                $filePath = "{$base}/{$file['filename']}";

                if (file_put_contents($filePath, $file['content']) === false) {
                    $this->components->error("Failed to write: {$filePath}");

                    return self::FAILURE;
                }
            }

            $this->components->info('TypeScript definitions written to: ' . $base);
        }

        return self::SUCCESS;
    }
}
