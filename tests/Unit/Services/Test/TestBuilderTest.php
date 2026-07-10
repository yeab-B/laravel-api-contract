<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Test;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Test\TestBuilder;

class TestBuilderTest extends TestCase
{
    private TestBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new TestBuilder();
    }

    public function test_reset_clears_output(): void
    {
        $this->builder->line('something');
        $this->builder->reset();

        $this->assertSame('', $this->builder->getOutput());
    }

    public function test_write_php_tag(): void
    {
        $this->builder->writePhpTag();

        $this->assertStringContainsString('<?php', $this->builder->getOutput());
    }

    public function test_write_declare_strict(): void
    {
        $this->builder->writeDeclareStrict();

        $this->assertStringContainsString('declare(strict_types=1);', $this->builder->getOutput());
    }

    public function test_write_namespace(): void
    {
        $this->builder->writeNamespace('Tests\Feature\API');

        $this->assertStringContainsString('namespace Tests\Feature\API;', $this->builder->getOutput());
    }

    public function test_write_use(): void
    {
        $this->builder->writeUse('Tests\TestCase');

        $this->assertStringContainsString('use Tests\TestCase;', $this->builder->getOutput());
    }

    public function test_open_and_close_class(): void
    {
        $this->builder->openClass('UserApiTest');
        $this->builder->closeClass();

        $output = $this->builder->getOutput();

        $this->assertStringContainsString('class UserApiTest extends TestCase', $output);
        $this->assertStringContainsString('{', $output);
        $this->assertStringContainsString('}', $output);
    }

    public function test_open_and_close_method(): void
    {
        $this->builder->openMethod('test_can_list_users');
        $this->builder->closeMethod();

        $output = $this->builder->getOutput();

        $this->assertStringContainsString('public function test_can_list_users(): void', $output);
    }

    public function test_write_payload(): void
    {
        $field = new \Yab\LaravelApiContract\Services\DTO\ValidationField(
            name: 'email',
            type: 'email',
            required: true,
            rules: ['required', 'email'],
        );

        $this->builder->writePayload([$field]);

        $output = $this->builder->getOutput();

        $this->assertStringContainsString("'email' => 'john@example.com'", $output);
    }

    public function test_write_empty_payload(): void
    {
        $this->builder->writeEmptyPayload();

        $this->assertStringContainsString('$payload = [];', $this->builder->getOutput());
    }

    public function test_write_uri(): void
    {
        $this->builder->writeUri('/api/users/1');

        $this->assertStringContainsString("\$uri = '/api/users/1';", $this->builder->getOutput());
    }

    public function test_write_get(): void
    {
        $this->builder->writeGet('/api/users');

        $this->assertStringContainsString("\$response = \$this->getJson('/api/users');", $this->builder->getOutput());
    }

    public function test_write_post(): void
    {
        $this->builder->writePost('/api/users');

        $this->assertStringContainsString("\$response = \$this->postJson('/api/users', \$payload);", $this->builder->getOutput());
    }

    public function test_write_put(): void
    {
        $this->builder->writePut('/api/users/1');

        $this->assertStringContainsString("\$response = \$this->putJson('/api/users/1', \$payload);", $this->builder->getOutput());
    }

    public function test_write_patch(): void
    {
        $this->builder->writePatch('/api/users/1');

        $this->assertStringContainsString("\$response = \$this->patchJson('/api/users/1', \$payload);", $this->builder->getOutput());
    }

    public function test_write_delete(): void
    {
        $this->builder->writeDelete('/api/users/1');

        $this->assertStringContainsString("\$response = \$this->deleteJson('/api/users/1');", $this->builder->getOutput());
    }

    public function test_write_assert_ok(): void
    {
        $this->builder->writeAssertOk();

        $this->assertStringContainsString('$response->assertOk();', $this->builder->getOutput());
    }

    public function test_write_assert_created(): void
    {
        $this->builder->writeAssertCreated();

        $this->assertStringContainsString('$response->assertCreated();', $this->builder->getOutput());
    }

    public function test_write_assert_no_content(): void
    {
        $this->builder->writeAssertNoContent();

        $this->assertStringContainsString('$response->assertNoContent();', $this->builder->getOutput());
    }

    public function test_write_assert_unauthorized(): void
    {
        $this->builder->writeAssertUnauthorized();

        $this->assertStringContainsString('$response->assertUnauthorized();', $this->builder->getOutput());
    }

    public function test_write_assert_unprocessable(): void
    {
        $this->builder->writeAssertUnprocessable();

        $this->assertStringContainsString('$response->assertStatus(422);', $this->builder->getOutput());
    }
}
