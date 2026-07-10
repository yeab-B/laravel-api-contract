<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

interface PostmanGeneratorContract
{
    public function generate(ApiContractContract $contract): string;
}
