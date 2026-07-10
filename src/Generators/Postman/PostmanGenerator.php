<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Generators\Postman;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Contracts\PostmanGeneratorContract;
use Yab\LaravelApiContract\Services\Postman\PostmanBuilder;

class PostmanGenerator implements PostmanGeneratorContract
{
    public function __construct(
        private readonly PostmanBuilder $builder,
    ) {
    }

    public function generate(ApiContractContract $contract): string
    {
        $collection = $this->builder->build($contract);

        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode Postman collection: ' . json_last_error_msg());
        }

        return $json;
    }
}
