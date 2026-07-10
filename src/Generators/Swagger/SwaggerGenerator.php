<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Generators\Swagger;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Contracts\SwaggerGeneratorContract;
use Yab\LaravelApiContract\Services\OpenApi\OpenApiBuilder;

class SwaggerGenerator implements SwaggerGeneratorContract
{
    public function __construct(
        private readonly OpenApiBuilder $builder,
    ) {
    }

    public function generate(ApiContractContract $contract): string
    {
        $document = $this->builder->build($contract);

        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode Swagger document: ' . json_last_error_msg());
        }

        return $json;
    }
}
