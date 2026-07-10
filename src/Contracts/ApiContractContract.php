<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

interface ApiContractContract
{
    public function name(): string;

    public function version(): string;

    /** @return array<int, \Yab\LaravelApiContract\Services\Contract\EndpointDefinition> */
    public function endpoints(): array;

    public function authentication(): string;

    /** @return array<string, mixed> */
    public function metadata(): array;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
