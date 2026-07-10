<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Contract;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class EndpointDefinitionTest extends TestCase
{
    public function test_constructs_with_minimal_arguments(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/health',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
        );

        $this->assertSame('GET', $endpoint->method());
        $this->assertSame('api/health', $endpoint->uri());
        $this->assertNull($endpoint->name());
        $this->assertNull($endpoint->controller());
        $this->assertSame([], $endpoint->middleware());
        $this->assertSame([], $endpoint->parameters());
        $this->assertNull($endpoint->request());
        $this->assertNull($endpoint->response());
        $this->assertFalse($endpoint->hasRequest());
        $this->assertFalse($endpoint->hasResponse());
    }

    public function test_constructs_with_full_arguments(): void
    {
        $request = new RequestDefinition(
            className: 'App\Http\Requests\StoreUserRequest',
            fields: [
                new ValidationField('name', 'string', true, ['required', 'string']),
            ],
            authorizeMethod: true,
            rawRules: ['name' => 'required|string'],
        );

        $response = new ResourceDefinition(
            resourceClass: 'App\Http\Resources\UserResource',
            fields: [
                new ResponseField('id', 'integer', false, '$this->id'),
            ],
            relationships: [],
            collection: false,
        );

        $endpoint = new EndpointDefinition(
            method: 'POST',
            uri: 'api/users',
            name: 'users.store',
            controller: 'UserController@store',
            middleware: ['api', 'auth:sanctum'],
            parameters: [],
            request: $request,
            response: $response,
        );

        $this->assertSame('POST', $endpoint->method());
        $this->assertSame('api/users', $endpoint->uri());
        $this->assertSame('users.store', $endpoint->name());
        $this->assertSame('UserController@store', $endpoint->controller());
        $this->assertSame(['api', 'auth:sanctum'], $endpoint->middleware());
        $this->assertTrue($endpoint->hasRequest());
        $this->assertTrue($endpoint->hasResponse());
        $this->assertSame($request, $endpoint->request());
        $this->assertSame($response, $endpoint->response());
    }

    public function test_to_array(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: 'users.index',
            controller: 'UserController@index',
            middleware: ['api'],
            parameters: ['id'],
        );

        $array = $endpoint->toArray();

        $this->assertSame('GET', $array['method']);
        $this->assertSame('api/users', $array['uri']);
        $this->assertSame('users.index', $array['name']);
        $this->assertSame('UserController@index', $array['controller']);
        $this->assertSame(['api'], $array['middleware']);
        $this->assertSame(['id'], $array['parameters']);
        $this->assertNull($array['request']);
        $this->assertNull($array['response']);
    }

    public function test_key_returns_method_and_uri(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []);

        $this->assertSame('GET api/users', $endpoint->key());
    }

    public function test_from_array_round_trip(): void
    {
        $request = new RequestDefinition(
            'App\Http\Requests\StoreUserRequest',
            [new ValidationField('name', 'string', true, ['required'])],
            true,
            [],
        );

        $response = new ResourceDefinition(
            'App\Http\Resources\UserResource',
            [new ResponseField('id', 'integer', false, '$this->id')],
            [],
            false,
        );

        $original = new EndpointDefinition(
            'POST',
            'api/users',
            'users.store',
            'UserController@store',
            ['api', 'auth'],
            ['id'],
            $request,
            $response,
        );

        $restored = EndpointDefinition::fromArray($original->toArray());

        $this->assertSame($original->method(), $restored->method());
        $this->assertSame($original->uri(), $restored->uri());
        $this->assertSame($original->name(), $restored->name());
        $this->assertSame($original->key(), $restored->key());
    }
}
