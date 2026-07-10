<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Contract;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;

class ApiContractTest extends TestCase
{
    public function test_constructs_with_minimal_arguments(): void
    {
        $contract = new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [],
            authentication: 'sanctum',
        );

        $this->assertSame('Test API', $contract->name());
        $this->assertSame('1.0', $contract->version());
        $this->assertSame([], $contract->endpoints());
        $this->assertSame('sanctum', $contract->authentication());
        $this->assertSame([], $contract->metadata());
    }

    public function test_constructs_with_endpoints(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
        );

        $contract = new ApiContract(
            name: 'Laravel API',
            version: '2.0',
            endpoints: [$endpoint],
            authentication: 'passport',
            metadata: ['key' => 'value'],
        );

        $this->assertCount(1, $contract->endpoints());
        $this->assertSame('passport', $contract->authentication());
        $this->assertSame(['key' => 'value'], $contract->metadata());
    }

    public function test_to_array(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: 'users.index',
            controller: 'UserController@index',
            middleware: ['api'],
            parameters: [],
        );

        $contract = new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'sanctum',
            metadata: ['generated_at' => '2025-01-01'],
        );

        $array = $contract->toArray();

        $this->assertSame('Test API', $array['name']);
        $this->assertSame('1.0', $array['version']);
        $this->assertSame('sanctum', $array['authentication']);
        $this->assertCount(1, $array['endpoints']);
        $this->assertSame('GET', $array['endpoints'][0]['method']);
        $this->assertSame('api/users', $array['endpoints'][0]['uri']);
        $this->assertArrayHasKey('metadata', $array);
    }

    public function test_implements_contract_interface(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $this->assertInstanceOf(\Yab\LaravelApiContract\Contracts\ApiContractContract::class, $contract);
    }

    public function test_from_array_round_trip(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', 'users.index', 'UserController@index', ['api'], []);

        $original = new ApiContract('Test API', '1.5', [$endpoint], 'sanctum', ['key' => 'value']);

        $restored = ApiContract::fromArray($original->toArray());

        $this->assertSame($original->name(), $restored->name());
        $this->assertSame($original->version(), $restored->version());
        $this->assertSame($original->authentication(), $restored->authentication());
        $this->assertSame($original->metadata(), $restored->metadata());
        $this->assertCount(count($original->endpoints()), $restored->endpoints());
    }
}
