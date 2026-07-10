<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'api-contract:install
        {--force : Overwrite existing configuration}';

    protected $description = 'Install the Laravel API Contract package and publish its configuration.';

    public function handle(): int
    {
        $this->components->info('Installing Laravel API Contract...');

        $this->publishConfiguration();

        $this->displayPackageInformation();

        $this->components->info('Installation complete.');

        return Command::SUCCESS;
    }

    private function publishConfiguration(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'api-contract-config',
            '--force' => $this->option('force'),
        ]);
    }

    private function displayPackageInformation(): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Package</>', 'Laravel API Contract');
        $this->components->twoColumnDetail('<fg=green>Version</>', '1.0.0');

        $configPath = config_path('api-contract.php');

        if (File::exists($configPath)) {
            $this->components->twoColumnDetail('<fg=green>Configuration</>', $configPath);
        }

        $this->newLine();
        $this->components->bulletList([
            'Define your API contracts using PHP 8 attributes.',
            'Generate TypeScript types, OpenAPI specs, Postman collections, and more.',
            'Run <comment>php artisan api-contract:generate</comment> when generators are ready.',
        ]);
    }
}
