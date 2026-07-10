<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Support\ContractSerializer;

class CompareCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    protected $signature = 'api-contract:compare
        {--old= : Path to the old contract JSON file}
        {--new= : Path to the new contract JSON file}
        {--format= : Output format (json or markdown)}
        {--output= : Optional file path to write the report}';

    protected $description = 'Compare two API contract snapshots and detect breaking changes.';

    public function __construct(
        private readonly ContractSerializer $serializer,
        private readonly \Yab\LaravelApiContract\Config\Configuration $configuration,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $oldPath = $this->option('old');
        $newPath = $this->option('new');

        if (!is_string($oldPath) || !is_string($newPath)) {
            $this->error('Both --old and --new options are required.');

            return self::FAILURE;
        }

        if (!file_exists($oldPath)) {
            $this->error("Old contract file not found: {$oldPath}");

            return self::FAILURE;
        }

        if (!file_exists($newPath)) {
            $this->error("New contract file not found: {$newPath}");

            return self::FAILURE;
        }

        $this->configuration->ensureSafePath($oldPath);
        $this->configuration->ensureSafePath($newPath);

        $old = $this->serializer->fromFile($oldPath);
        $new = $this->serializer->fromFile($newPath);

        $this->line('Package: Laravel API Contract');
        /** @var \Yab\LaravelApiContract\Contracts\ContractComparatorContract $comparator */
        $comparator = $this->laravel->make(\Yab\LaravelApiContract\Contracts\ContractComparatorContract::class);
        $report = $comparator->compare($old, $new);

        $format = $this->option('format');
        $outputPath = $this->option('output');

        if (is_string($outputPath)) {
            $this->configuration->ensureSafePath($outputPath);
            $content = $format === 'markdown' ? $report->toMarkdown() : $report->toJson();
            file_put_contents($outputPath, $content);
            $this->line('Report saved to: ' . $outputPath);
        } elseif ($format === 'markdown') {
            $this->line($report->toMarkdown());
        } else {
            $this->line($report->toJson());
        }

        if ($report->hasBreakingChanges()) {
            $this->newLine();
            $this->warn('Breaking changes detected!');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('No breaking changes detected.');

        return self::SUCCESS;
    }
}
