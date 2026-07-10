<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Support;

use Yab\LaravelApiContract\Config\Configuration;
use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Services\Contract\ApiContract;

class ContractSerializer
{
    private readonly ?Configuration $configuration;

    public function __construct(
        ?Configuration $configuration = null,
    ) {
        $this->configuration = $configuration;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(ApiContractContract $contract): array
    {
        return $contract->toArray();
    }

    public function toJson(ApiContractContract $contract, bool $pretty = true): string
    {
        $flags = $pretty ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 0;

        $result = json_encode($this->toArray($contract), $flags);

        if ($result === false) {
            throw new \RuntimeException('Failed to serialize contract to JSON: ' . json_last_error_msg());
        }

        return $result;
    }

    public function toFile(ApiContractContract $contract, string $path, bool $pretty = true): void
    {
        if ($this->configuration !== null) {
            $this->configuration->ensureSafePath($path);
        }

        $directory = dirname($path);

        if (!is_dir($directory)) {
            // @codeCoverageIgnoreStart
            mkdir($directory, 0755, true);
            // @codeCoverageIgnoreEnd
        }

        file_put_contents($path, $this->toJson($contract, $pretty));
    }

    public function fromJson(string $json): ApiContractContract
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return ApiContract::fromArray($data);
    }

    public function fromFile(string $path): ApiContractContract
    {
        if ($this->configuration !== null) {
            $this->configuration->ensureSafePath($path);
        }

        if (!file_exists($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('Contract file "%s" does not exist or is not readable.', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException(sprintf('Failed to read contract file "%s".', $path));
        }

        return $this->fromJson($contents);
    }
}
