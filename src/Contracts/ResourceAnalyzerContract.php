<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;

interface ResourceAnalyzerContract
{
    public function analyze(ControllerDefinition $definition): ?ResourceDefinition;

    public function analyzeResource(string $resourceClass): ?ResourceDefinition;
}
