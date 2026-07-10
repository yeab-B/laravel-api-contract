<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Comparison;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Comparison\ApiChange;

class ApiChangeTest extends TestCase
{
    public function test_constructs_and_exposes_properties(): void
    {
        $change = new ApiChange(
            type: ApiChange::REMOVED_ENDPOINT,
            location: 'GET api/users',
            description: 'Endpoint has been removed.',
        );

        $this->assertSame(ApiChange::REMOVED_ENDPOINT, $change->type());
        $this->assertSame('GET api/users', $change->location());
        $this->assertSame('Endpoint has been removed.', $change->description());
        $this->assertTrue($change->isBreaking());
        $this->assertSame(ApiChange::BREAKING, $change->severity());
    }

    public function test_added_endpoint_is_non_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::ADDED_ENDPOINT,
            location: 'POST api/posts',
            description: 'Endpoint has been added.',
        );

        $this->assertFalse($change->isBreaking());
        $this->assertSame(ApiChange::NON_BREAKING, $change->severity());
    }

    public function test_added_request_field_is_non_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::ADDED_REQUEST_FIELD,
            location: 'POST api/users/request/email',
            description: 'Field added.',
        );

        $this->assertFalse($change->isBreaking());
        $this->assertSame(ApiChange::NON_BREAKING, $change->severity());
    }

    public function test_added_response_field_is_non_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::ADDED_RESPONSE_FIELD,
            location: 'GET api/users/response/email',
            description: 'Field added.',
        );

        $this->assertFalse($change->isBreaking());
        $this->assertSame(ApiChange::NON_BREAKING, $change->severity());
    }

    public function test_removed_request_field_is_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::REMOVED_REQUEST_FIELD,
            location: 'POST api/posts/request/title',
            description: 'Field removed.',
        );

        $this->assertTrue($change->isBreaking());
    }

    public function test_to_array(): void
    {
        $change = new ApiChange(
            type: ApiChange::CHANGED_AUTH,
            location: 'global',
            description: 'Auth changed from sanctum to passport.',
        );

        $expected = [
            'type' => ApiChange::CHANGED_AUTH,
            'location' => 'global',
            'description' => 'Auth changed from sanctum to passport.',
            'severity' => ApiChange::BREAKING,
        ];

        $this->assertSame($expected, $change->toArray());
    }

    public function test_changed_method_is_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::CHANGED_METHOD,
            location: 'GET api/users',
            description: 'Method changed from GET to POST.',
        );

        $this->assertTrue($change->isBreaking());
    }

    public function test_changed_auth_is_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::CHANGED_AUTH,
            location: 'global',
            description: 'Auth changed.',
        );

        $this->assertTrue($change->isBreaking());
    }

    public function test_changed_request_field_type_is_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::CHANGED_REQUEST_FIELD_TYPE,
            location: 'POST api/posts/request/title',
            description: 'Type changed from string to integer.',
        );

        $this->assertTrue($change->isBreaking());
    }

    public function test_changed_response_field_type_is_breaking(): void
    {
        $change = new ApiChange(
            type: ApiChange::CHANGED_RESPONSE_FIELD_TYPE,
            location: 'GET api/posts/response/title',
            description: 'Type changed from string to integer.',
        );

        $this->assertTrue($change->isBreaking());
    }
}
