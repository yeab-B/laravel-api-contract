<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

interface ContractBuilderContract
{
    public function build(): ApiContractContract;
}
