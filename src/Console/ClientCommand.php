<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ClientGeneratorContract;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Config\Configuration;

class ClientCommand extends Command
{
    protected $signature = 'api-contract:client
                            {--output= : Path to the directory where client files will be written}';

    protected $description = 'Generate a typed TypeScript API client from the API contract';

    public function handle(
        ContractBuilderContract $builder,
        ClientGeneratorContract $generator,
        Configuration $config,
    ): int {
        $this->info('Generating TypeScript API client from contract...');

        $contract = $builder->build();

        $files = $generator->generate($contract);

        if ($files === []) {
            $this->warn('No endpoints found; no client files generated.');

            return Command::SUCCESS;
        }

        $outputPath = $this->option('output');

        if ($outputPath === null || $outputPath === false || is_array($outputPath)) {
            foreach ($files as $file) {
                $this->line("--- {$file['filename']} ---");
                $this->line($file['content']);
            }

            return Command::SUCCESS;
        }

        $outputPath = (string) $outputPath;

        $config->ensureSafePath($outputPath);

        if (!is_dir($outputPath)) {
            if (!mkdir($outputPath, 0755, true) && !is_dir($outputPath)) {
                $this->error("Failed to create directory: {$outputPath}");

                return Command::FAILURE;
            }
        }

        foreach ($files as $file) {
            $filePath = rtrim($outputPath, '/') . '/' . $file['filename'];

            if (file_put_contents($filePath, $file['content']) === false) {
                $this->error("Failed to write: {$filePath}");

                return Command::FAILURE;
            }
        }

        $this->info('TypeScript API client written to: ' . $outputPath);

        return Command::SUCCESS;
    }
}
