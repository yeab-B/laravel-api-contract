<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RequestAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;

class RequestsCommand extends Command
{
    protected $signature = 'api-contract:requests';

    protected $description = 'Analyze Form Request classes from discovered API routes.';

    public function __construct(
        private readonly RouteAnalyzerContract $routeAnalyzer,
        private readonly ControllerAnalyzerContract $controllerAnalyzer,
        private readonly RequestAnalyzerContract $requestAnalyzer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $routes = $this->routeAnalyzer->discover();

        if ($routes->isEmpty()) {
            $this->warn('No API routes discovered. Nothing to analyze.');

            return self::SUCCESS;
        }

        $analyzed = 0;
        $skipped = 0;

        foreach ($routes->all() as $route) {
            $controllerDefinition = $this->controllerAnalyzer->analyze($route);

            if ($controllerDefinition === null) {
                $skipped++;

                continue;
            }

            $requestDefinition = $this->requestAnalyzer->analyze($controllerDefinition);

            $this->newLine();
            $this->components->twoColumnDetail(
                '<fg=yellow>Route</>',
                sprintf('%s %s', $route->method(), $route->uri()),
            );

            if ($requestDefinition === null) {
                $this->components->twoColumnDetail('Form Request', '<fg=gray>none</>');
                $skipped++;

                continue;
            }

            $this->components->twoColumnDetail('Form Request', $requestDefinition->className());

            $fields = $requestDefinition->fields();

            if ($fields === []) {
                $this->components->twoColumnDetail('Fields', '<fg=gray>none</>');
            } else {
                $this->line('  <fg=cyan>Fields:</>');

                foreach ($fields as $field) {
                    $this->line(sprintf('    <fg=white>%s</>', $field->name()));
                    $this->components->twoColumnDetail('      Type', $field->type());
                    $this->components->twoColumnDetail(
                        '      Required',
                        $field->required() ? '<fg=green>yes</>' : '<fg=gray>no</>',
                    );
                }
            }

            $analyzed++;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Analyzed', (string) $analyzed);
        $this->components->twoColumnDetail('Skipped', (string) $skipped);

        return self::SUCCESS;
    }
}
