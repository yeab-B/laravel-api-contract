<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

use Yab\LaravelApiContract\Services\DTO\RouteCollection;

interface RouteAnalyzerContract
{
    public function discover(): RouteCollection;
}
