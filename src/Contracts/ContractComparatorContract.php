<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Contracts;

use Yab\LaravelApiContract\Services\Comparison\ChangeReport;

interface ContractComparatorContract
{
    public function compare(ApiContractContract $old, ApiContractContract $new): ChangeReport;
}
