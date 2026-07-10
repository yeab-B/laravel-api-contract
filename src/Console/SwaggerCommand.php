<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Contracts\SwaggerGeneratorContract;
use Yab\LaravelApiContract\Config\Configuration;

class SwaggerCommand extends Command
{
    protected $signature = 'api-contract:swagger
                            {--pretty : Pretty-print the JSON output}
                            {--path= : Path to write the Swagger JSON file}';

    protected $description = 'Generate a Swagger/OpenAPI 3.0 JSON document from the API contract';

    public function handle(
        ContractBuilderContract $builder,
        SwaggerGeneratorContract $generator,
        Configuration $config,
    ): int {
        $this->components->info('Generating Swagger document from API contract...');

        $contract = $builder->build();

        $json = $generator->generate($contract);

        $path = $this->option('path');

        if ($path === null || $path === false || is_array($path)) {
            $this->line($json);

            return self::SUCCESS;
        }

        $path = (string) $path;

        $config->ensureSafePath($path);

        $directory = dirname($path);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                $this->components->error("Failed to create directory: {$directory}");

                return self::FAILURE;
            }
        }

        $flags = $this->option('pretty') ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;

        $decoded = json_decode($json, true);
        $formatted = json_encode($decoded, $flags);

        if ($formatted === false || file_put_contents($path, $formatted) === false) {
            $this->components->error("Failed to write Swagger document to: {$path}");

            return self::FAILURE;
        }

        $this->components->success("Swagger document written to: {$path}");

        return self::SUCCESS;
    }
}
