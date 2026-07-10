<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Contracts\MarkdownGeneratorContract;
use Yab\LaravelApiContract\Config\Configuration;

class MarkdownCommand extends Command
{
    protected $signature = 'api-contract:docs
                            {--path= : Path to write the Markdown documentation file}';

    protected $description = 'Generate Markdown API documentation from the API contract';

    public function handle(
        ContractBuilderContract $builder,
        MarkdownGeneratorContract $generator,
        Configuration $config,
    ): int {
        $this->components->info('Generating Markdown documentation from API contract...');

        $contract = $builder->build();

        $markdown = $generator->generate($contract);

        $path = $this->option('path');

        if ($path === null || $path === false || is_array($path)) {
            $this->line($markdown);

            return Command::SUCCESS;
        }

        $path = (string) $path;

        $config->ensureSafePath($path);

        $directory = dirname($path);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                $this->components->error("Failed to create directory: {$directory}");

                return Command::FAILURE;
            }
        }

        if (file_put_contents($path, $markdown) === false) {
            $this->components->error("Failed to write Markdown documentation to: {$path}");

            return Command::FAILURE;
        }

        $this->components->success("Markdown documentation written to: {$path}");

        return Command::SUCCESS;
    }
}
