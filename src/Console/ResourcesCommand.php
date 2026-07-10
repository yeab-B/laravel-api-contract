<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract;
use Yab\LaravelApiContract\Contracts\ResourceAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;

class ResourcesCommand extends Command
{
    protected $signature = 'api-contract:resources';

    protected $description = 'Analyze API Resources from discovered routes.';

    public function __construct(
        private readonly RouteAnalyzerContract $routeAnalyzer,
        private readonly ControllerAnalyzerContract $controllerAnalyzer,
        private readonly ResourceAnalyzerContract $resourceAnalyzer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $routes = $this->routeAnalyzer->discover();

        if ($routes->isEmpty()) {
            $this->warn('No API routes discovered. Nothing to analyze.');

            return Command::SUCCESS;
        }

        $analyzed = 0;
        $skipped = 0;

        foreach ($routes->all() as $route) {
            $controllerDefinition = $this->controllerAnalyzer->analyze($route);

            if ($controllerDefinition === null) {
                $skipped++;

                continue;
            }

            $resourceDefinition = $this->resourceAnalyzer->analyze($controllerDefinition);

            $this->newLine();
            $this->components->twoColumnDetail(
                '<fg=yellow>Route</>',
                sprintf('%s %s', $route->method(), $route->uri()),
            );

            if ($resourceDefinition === null) {
                $this->components->twoColumnDetail('Resource', '<fg=gray>none</>');
                $skipped++;

                continue;
            }

            $this->components->twoColumnDetail('Resource', $resourceDefinition->resourceClass());

            if ($resourceDefinition->collection()) {
                $this->components->twoColumnDetail('Type', '<fg=cyan>collection</>');
            }

            $fields = $resourceDefinition->fields();

            if ($fields === []) {
                $this->components->twoColumnDetail('Fields', '<fg=gray>none</>');
            } else {
                $this->line('  <fg=cyan>Response Fields:</>');

                foreach ($fields as $field) {
                    $typeDisplay = $field->type();
                    if ($field->isRelationship()) {
                        $typeDisplay = sprintf(
                            '<fg=magenta>%s</> %s',
                            $field->relationClass(),
                            $field->collection() ? '[]' : '',
                        );
                    }

                    $nullableDisplay = $field->nullable()
                        ? '<fg=yellow>?</>'
                        : '';

                    $this->line(sprintf(
                        '    <fg=white>%s</>  %s%s',
                        $field->name(),
                        $typeDisplay,
                        $nullableDisplay,
                    ));
                }
            }

            if ($resourceDefinition->hasRelationships()) {
                $this->line('  <fg=cyan>Relationships:</>');
                foreach ($resourceDefinition->relationships() as $rel) {
                    $this->line(sprintf('    <fg=magenta>%s</>', $rel));
                }
            }

            $analyzed++;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Analyzed', (string) $analyzed);
        $this->components->twoColumnDetail('Skipped', (string) $skipped);

        return Command::SUCCESS;
    }
}
