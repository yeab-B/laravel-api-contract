<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Contracts\TestGeneratorContract;
use Yab\LaravelApiContract\Config\Configuration;

class TestCommand extends Command
{
    protected $signature = 'api-contract:tests
                            {--output= : Path to the directory where test files will be written}';

    protected $description = 'Generate PHPUnit feature tests from the API contract';

    public function handle(
        ContractBuilderContract $builder,
        TestGeneratorContract $generator,
        Configuration $config,
    ): int {
        $this->components->info('Generating PHPUnit feature tests from API contract...');

        $contract = $builder->build();

        $files = $generator->generate($contract);

        if ($files === []) {
            $this->components->warn('No endpoints found; no test files generated.');

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

        if (!is_dir($outputPath)) {
            if (!mkdir($outputPath, 0755, true) && !is_dir($outputPath)) {
                $this->components->error("Failed to create directory: {$outputPath}");

                return self::FAILURE;
            }
        }

        foreach ($files as $file) {
            $filePath = rtrim($outputPath, '/') . '/' . $file['filename'];

            if (file_put_contents($filePath, $file['content']) === false) {
                $this->components->error("Failed to write: {$filePath}");

                return self::FAILURE;
            }
        }

        $this->components->success('PHPUnit feature tests written to: ' . $outputPath);

        return self::SUCCESS;
    }
}
