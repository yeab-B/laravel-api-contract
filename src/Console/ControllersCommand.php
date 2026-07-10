<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Console;

use Illuminate\Console\Command;
use Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;

class ControllersCommand extends Command
{
    protected $signature = 'api-contract:controllers';

    protected $description = 'Analyze controllers from discovered API routes.';

    public function __construct(
        private readonly RouteAnalyzerContract $routeAnalyzer,
        private readonly ControllerAnalyzerContract $controllerAnalyzer,
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
            $definition = $this->controllerAnalyzer->analyze($route);

            $this->newLine();
            $this->components->twoColumnDetail(
                '<fg=yellow>Route</>',
                sprintf('%s %s', $route->method(), $route->uri()),
            );

            if ($definition === null) {
                $this->components->twoColumnDetail('Controller', '<fg=red>unable to analyze</>');
                $skipped++;

                continue;
            }

            $this->components->twoColumnDetail(
                'Controller',
                sprintf('%s@%s', $definition->className(), $definition->method()),
            );
            $this->components->twoColumnDetail('Visibility', $definition->visibility());
            $this->components->twoColumnDetail('Return Type', $definition->returnType() ?? '<fg=gray>none</>');

            if ($definition->hasDependencies()) {
                $this->components->twoColumnDetail(
                    'Dependencies',
                    implode(', ', $definition->dependencies()),
                );
            }

            $params = $definition->parameters();

            if ($params === []) {
                $this->components->twoColumnDetail('Parameters', '<fg=gray>none</>');
            } else {
                foreach ($params as $param) {
                    $type = $param['type'] ?? '<fg=gray>untyped</>';
                    $this->components->twoColumnDetail(
                        sprintf('  $%s', $param['name']),
                        $type,
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
