<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;

interface RequestAnalyzerContract
{
    public function analyze(ControllerDefinition $definition): ?RequestDefinition;
}
