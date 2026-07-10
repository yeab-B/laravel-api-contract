<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Comparison;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Comparison\ApiChange;
use Yab\LaravelApiContract\Services\Comparison\ContractComparator;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class ContractComparatorTest extends TestCase
{
    private ContractComparator $comparator;

    protected function setUp(): void
    {
        $this->comparator = new ContractComparator();
    }

    public function test_identical_contracts_produce_no_changes(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []);
        $old = new ApiContract('API', '1.0', [$endpoint], 'sanctum');
        $new = new ApiContract('API', '1.0', [$endpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertSame([], $report->changes());
        $this->assertFalse($report->hasBreakingChanges());
    }

    public function test_detects_added_endpoint(): void
    {
        $old = new ApiContract('API', '1.0', [], 'sanctum');
        $endpoint = new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []);
        $new = new ApiContract('API', '2.0', [$endpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::ADDED_ENDPOINT, $report->changes()[0]->type());
        $this->assertSame('GET api/users', $report->changes()[0]->location());
    }

    public function test_detects_removed_endpoint(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []);
        $old = new ApiContract('API', '1.0', [$endpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::REMOVED_ENDPOINT, $report->changes()[0]->type());
        $this->assertTrue($report->hasBreakingChanges());
    }

    public function test_detects_multiple_endpoint_additions_and_removals(): void
    {
        $oldEndpoint = new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []);
        $newEndpoint = new EndpointDefinition('POST', 'api/posts', 'posts.store', null, [], []);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(2, $report->changes());
    }

    public function test_detects_changed_authentication(): void
    {
        $old = new ApiContract('API', '1.0', [], 'sanctum');
        $new = new ApiContract('API', '2.0', [], 'passport');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::CHANGED_AUTH, $report->changes()[0]->type());
        $this->assertTrue($report->hasBreakingChanges());
    }

    public function test_detects_changed_http_method(): void
    {
        $oldEndpoint = new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []);
        $newEndpoint = new EndpointDefinition('POST', 'api/users', 'users.index', null, [], []);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::CHANGED_METHOD, $report->changes()[0]->type());
    }

    public function test_detects_added_request_field(): void
    {
        $oldField = new ValidationField('name', 'string', true, ['required']);
        $newField1 = new ValidationField('name', 'string', true, ['required']);
        $newField2 = new ValidationField('email', 'email', true, ['required', 'email']);

        $oldRequest = new RequestDefinition('OldRequest', [$oldField], true, []);
        $newRequest = new RequestDefinition('NewRequest', [$newField1, $newField2], true, []);

        $oldEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], $oldRequest, null);
        $newEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], $newRequest, null);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::ADDED_REQUEST_FIELD, $report->changes()[0]->type());
        $this->assertSame('POST api/users/request/email', $report->changes()[0]->location());
    }

    public function test_detects_removed_request_field(): void
    {
        $oldField1 = new ValidationField('name', 'string', true, ['required']);
        $oldField2 = new ValidationField('email', 'email', true, ['required']);
        $newField = new ValidationField('name', 'string', true, ['required']);

        $oldRequest = new RequestDefinition('OldRequest', [$oldField1, $oldField2], true, []);
        $newRequest = new RequestDefinition('NewRequest', [$newField], true, []);

        $oldEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], $oldRequest, null);
        $newEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], $newRequest, null);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::REMOVED_REQUEST_FIELD, $report->changes()[0]->type());
        $this->assertTrue($report->hasBreakingChanges());
    }

    public function test_detects_changed_request_field_type(): void
    {
        $oldField = new ValidationField('age', 'integer', false, []);
        $newField = new ValidationField('age', 'string', false, []);

        $oldRequest = new RequestDefinition('OldRequest', [$oldField], true, []);
        $newRequest = new RequestDefinition('NewRequest', [$newField], true, []);

        $oldEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], $oldRequest, null);
        $newEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], $newRequest, null);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::CHANGED_REQUEST_FIELD_TYPE, $report->changes()[0]->type());
    }

    public function test_detects_added_response_field(): void
    {
        $oldField = new ResponseField('id', 'integer', false, '$this->id');
        $newField1 = new ResponseField('id', 'integer', false, '$this->id');
        $newField2 = new ResponseField('name', 'string', false, '$this->name');

        $oldResponse = new ResourceDefinition('OldResource', [$oldField], [], false);
        $newResponse = new ResourceDefinition('NewResource', [$newField1, $newField2], [], false);

        $oldEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, $oldResponse);
        $newEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, $newResponse);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::ADDED_RESPONSE_FIELD, $report->changes()[0]->type());
    }

    public function test_detects_removed_response_field(): void
    {
        $oldField1 = new ResponseField('id', 'integer', false, '$this->id');
        $oldField2 = new ResponseField('name', 'string', false, '$this->name');
        $newField = new ResponseField('id', 'integer', false, '$this->id');

        $oldResponse = new ResourceDefinition('OldResource', [$oldField1, $oldField2], [], false);
        $newResponse = new ResourceDefinition('NewResource', [$newField], [], false);

        $oldEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, $oldResponse);
        $newEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, $newResponse);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::REMOVED_RESPONSE_FIELD, $report->changes()[0]->type());
    }

    public function test_detects_changed_response_field_type(): void
    {
        $oldField = new ResponseField('id', 'integer', false, '$this->id');
        $newField = new ResponseField('id', 'string', false, '$this->id');

        $oldResponse = new ResourceDefinition('OldResource', [$oldField], [], false);
        $newResponse = new ResourceDefinition('NewResource', [$newField], [], false);

        $oldEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, $oldResponse);
        $newEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, $newResponse);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::CHANGED_RESPONSE_FIELD_TYPE, $report->changes()[0]->type());
    }

    public function test_detects_request_definition_added(): void
    {
        $newRequest = new RequestDefinition('NewRequest', [], true, []);
        $oldEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], null, null);
        $newEndpoint = new EndpointDefinition('POST', 'api/users', 'users.store', null, [], [], $newRequest, null);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::ADDED_REQUEST_FIELD, $report->changes()[0]->type());
    }

    public function test_detects_response_definition_removed(): void
    {
        $oldResponse = new ResourceDefinition('OldResource', [], [], false);
        $oldEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, $oldResponse);
        $newEndpoint = new EndpointDefinition('GET', 'api/users', 'users.show', null, [], [], null, null);

        $old = new ApiContract('API', '1.0', [$oldEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$newEndpoint], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::REMOVED_RESPONSE_FIELD, $report->changes()[0]->type());
    }

    public function test_combines_multiple_changes(): void
    {
        $oldEndpoint = new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []);
        $keptEndpoint = new EndpointDefinition('GET', 'api/posts', 'posts.index', null, [], []);

        $old = new ApiContract('API', '1.0', [$oldEndpoint, $keptEndpoint], 'sanctum');
        $new = new ApiContract('API', '2.0', [$keptEndpoint], 'passport');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(2, $report->changes());
    }

    public function test_preserves_endpoint_order_in_report(): void
    {
        $endpoint1 = new EndpointDefinition('GET', 'api/a', null, null, [], []);
        $endpoint2 = new EndpointDefinition('GET', 'api/b', null, null, [], []);

        $old = new ApiContract('API', '1.0', [$endpoint1, $endpoint2], 'sanctum');
        $new = new ApiContract('API', '2.0', [$endpoint2], 'sanctum');

        $report = $this->comparator->compare($old, $new);

        $this->assertCount(1, $report->changes());
        $this->assertSame(ApiChange::REMOVED_ENDPOINT, $report->changes()[0]->type());
        $this->assertSame('GET api/a', $report->changes()[0]->location());
    }
}
