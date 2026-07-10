<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Generators\Markdown;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Contracts\MarkdownGeneratorContract;
use Yab\LaravelApiContract\Services\Markdown\MarkdownBuilder;

class MarkdownGenerator implements MarkdownGeneratorContract
{
    public function __construct(
        private readonly MarkdownBuilder $builder,
    ) {
    }

    public function generate(ApiContractContract $contract): string
    {
        return $this->builder->build($contract);
    }
}
