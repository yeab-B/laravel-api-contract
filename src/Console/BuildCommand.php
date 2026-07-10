<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Support\ContractSerializer;

class BuildCommand extends Command
{
    protected $signature = 'api-contract:build
        {--path= : The output path for the contract JSON file}
        {--pretty : Pretty-print the JSON output}';

    protected $description = 'Build the API contract from discovered routes.';

    public function __construct(
        private readonly ContractBuilderContract $builder,
        private readonly ContractSerializer $serializer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Building API contract...');

        $contract = $this->builder->build();

        $endpointCount = count($contract->endpoints());

        if ($endpointCount === 0) {
            $this->warn('No API endpoints discovered. Contract is empty.');
        } else {
            $this->line('Endpoints discovered: ' . (string) $endpointCount);
        }

        $pathOption = $this->option('path');
        $path = is_string($pathOption) ? $pathOption : storage_path('api-contract.json');

        $prettyOption = $this->option('pretty');
        $pretty = !(is_bool($prettyOption) && $prettyOption === false);

        $this->serializer->toFile($contract, $path, $pretty);

        $this->line('Contract saved to: ' . $path);

        return Command::SUCCESS;
    }
}
