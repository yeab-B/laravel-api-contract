<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

interface ContractRepositoryInterface
{
    /** @return array<int, mixed> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function findByPath(string $path): ?array;
}
