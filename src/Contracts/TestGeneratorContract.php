<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

interface TestGeneratorContract
{
    /**
     * @return array<int, array{filename: string, content: string}>
     */
    public function generate(ApiContractContract $contract): array;
}
