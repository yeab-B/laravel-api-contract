<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

interface GeneratorInterface
{
    public function name(): string;

    public function generate(): string;
}
