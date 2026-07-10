<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;

class RoutesCommand extends Command
{
    protected $signature = 'api-contract:routes';

    protected $description = 'Discover and display all registered API routes.';

    public function __construct(
        private readonly RouteAnalyzerContract $analyzer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $collection = $this->analyzer->discover();

        if ($collection->isEmpty()) {
            $this->warn('No API routes discovered.');

            return self::SUCCESS;
        }

        $headers = ['Method', 'URI', 'Name', 'Controller', 'Middleware'];
        $rows = [];

        foreach ($collection->all() as $route) {
            $rows[] = [
                $route->method(),
                $route->uri(),
                $route->name() ?? '-',
                $route->controller() ?? '-',
                implode(', ', $route->middleware()) ?: '-',
            ];
        }

        $this->table($headers, $rows);

        $this->components->twoColumnDetail(
            'Total API routes discovered',
            (string) $collection->count(),
        );

        return self::SUCCESS;
    }
}
