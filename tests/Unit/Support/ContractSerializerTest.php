<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Support\ContractSerializer;

class ContractSerializerTest extends TestCase
{
    private ContractSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new ContractSerializer();
    }

    public function test_to_array(): void
    {
        $contract = $this->makeContract();
        $array = $this->serializer->toArray($contract);

        $this->assertSame('Test API', $array['name']);
        $this->assertCount(1, $array['endpoints']);
    }

    public function test_to_json(): void
    {
        $contract = $this->makeContract();
        $json = $this->serializer->toJson($contract);

        $this->assertJson($json);

        $decoded = json_decode($json, true);

        $this->assertSame('Test API', $decoded['name']);
        $this->assertCount(1, $decoded['endpoints']);
    }

    public function test_to_json_is_pretty(): void
    {
        $contract = $this->makeContract();
        $pretty = $this->serializer->toJson($contract, true);
        $compact = $this->serializer->toJson($contract, false);

        $this->assertStringContainsString("\n", $pretty);
        $this->assertStringNotContainsString("\n", $compact);
    }

    public function test_to_file_writes_json(): void
    {
        $contract = $this->makeContract();
        $path = tempnam(sys_get_temp_dir(), 'api-contract-');

        $this->serializer->toFile($contract, $path);

        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertJson($content);

        unlink($path);
    }

    public function test_serializer_works_with_interface_typehint(): void
    {
        $contract = $this->makeContract();

        $this->assertInstanceOf(ApiContractContract::class, $contract);
        $this->assertIsArray($this->serializer->toArray($contract));
        $this->assertIsString($this->serializer->toJson($contract));
    }

    private function makeContract(): ApiContract
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
        );

        return new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'sanctum',
        );
    }
}
