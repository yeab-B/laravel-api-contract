<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\RouteDefinition;

interface ControllerAnalyzerContract
{
    public function analyze(RouteDefinition $route): ?ControllerDefinition;
}
