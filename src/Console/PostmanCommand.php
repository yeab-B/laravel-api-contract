<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Contracts\PostmanGeneratorContract;
use Yab\LaravelApiContract\Config\Configuration;

class PostmanCommand extends Command
{
    protected $signature = 'api-contract:postman
                            {--pretty : Pretty-print the JSON output}
                            {--path= : Path to write the Postman collection JSON file}';

    protected $description = 'Generate a Postman Collection v2.1 JSON document from the API contract';

    public function handle(
        ContractBuilderContract $builder,
        PostmanGeneratorContract $generator,
        Configuration $config,
    ): int {
        $this->components->info('Generating Postman collection from API contract...');

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
            $this->components->error("Failed to write Postman collection to: {$path}");

            return self::FAILURE;
        }

        $this->components->success("Postman collection written to: {$path}");

        return self::SUCCESS;
    }
}
