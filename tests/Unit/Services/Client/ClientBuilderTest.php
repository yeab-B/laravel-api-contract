<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Client;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Client\ClientBuilder;

class ClientBuilderTest extends TestCase
{
    private ClientBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new ClientBuilder();
    }

    public function test_line(): void
    {
        $this->builder->line('hello');
        $this->builder->line('world');

        $this->assertSame("hello\nworld\n", $this->builder->getOutput());
    }

    public function test_blank_line(): void
    {
        $this->builder->line('a');
        $this->builder->blankLine();
        $this->builder->line('b');

        $this->assertSame("a\n\nb\n", $this->builder->getOutput());
    }

    public function test_indent_and_outdent(): void
    {
        $this->builder->line('start');
        $this->builder->indent();
        $this->builder->line('indented');
        $this->builder->outdent();
        $this->builder->line('end');

        $expected = "start\n    indented\nend\n";

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_outdent_does_not_go_below_zero(): void
    {
        $this->builder->outdent();
        $this->builder->line('top');

        $this->assertSame("top\n", $this->builder->getOutput());
    }

    public function test_comment(): void
    {
        $this->builder->comment('hello world');

        $this->assertSame("// hello world\n", $this->builder->getOutput());
    }

    public function test_reset(): void
    {
        $this->builder->line('foo');
        $this->builder->reset();
        $this->builder->line('bar');

        $this->assertSame("bar\n", $this->builder->getOutput());
    }

    public function test_named_import(): void
    {
        $this->builder->namedImport(['User', 'Post'], '../types');

        $this->assertSame("import { User, Post } from '../types';\n", $this->builder->getOutput());
    }

    public function test_named_type_import(): void
    {
        $this->builder->namedImport(['User'], '../types', true);

        $this->assertSame("import type { User } from '../types';\n", $this->builder->getOutput());
    }

    public function test_service_object(): void
    {
        // Function line at baseIndent=1, body at bodyIndent=2
        $functions = [
            "    getUsers: async (): Promise<User[]> => {\n        const response = await api.get('/api/users');\n        return response.data;\n    },\n",
        ];

        $this->builder->serviceObject('userService', $functions);

        $expected = <<<'TS'

export const userService = {
    getUsers: async (): Promise<User[]> => {
        const response = await api.get('/api/users');
        return response.data;
    },
};

TS;

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_build_async_function_at_base_indent_1(): void
    {
        $body = "        const response = await api.get('/api/users/1');\n        return response.data;\n";

        $fn = $this->builder->buildAsyncFunction('getUser', ['id: number'], 'User', $body, baseIndent: 1);

        $expected = "    getUser: async (id: number): Promise<User> => {\n        const response = await api.get('/api/users/1');\n        return response.data;\n    },\n";

        $this->assertSame($expected, $fn);
    }

    public function test_build_async_function_with_no_params_at_base_indent_1(): void
    {
        $body = "        const response = await api.get('/api/users');\n        return response.data;\n";

        $fn = $this->builder->buildAsyncFunction('getUsers', [], 'User[]', $body, baseIndent: 1);

        $expected = "    getUsers: async (): Promise<User[]> => {\n        const response = await api.get('/api/users');\n        return response.data;\n    },\n";

        $this->assertSame($expected, $fn);
    }

    public function test_build_api_call_body_at_body_indent_2(): void
    {
        $body = $this->builder->buildApiCallBody('get', '/api/users', 'User[]', bodyIndent: 2);

        $expected = "        const response = await api.get<User[]>('/api/users');\n        return response.data;\n";

        $this->assertSame($expected, $body);
    }

    public function test_build_api_call_body_post_with_data(): void
    {
        $body = $this->builder->buildApiCallBody('post', '/api/users', 'User', [], 'data', bodyIndent: 2);

        $expected = "        const response = await api.post<User>('/api/users', data);\n        return response.data;\n";

        $this->assertSame($expected, $body);
    }

    public function test_build_api_call_body_delete(): void
    {
        $body = $this->builder->buildApiCallBody('delete', '/api/users/{id}', null, ['id'], bodyIndent: 2);

        $expected = "        const response = await api.delete(`/api/users/\${id}`);\n";

        $this->assertSame($expected, $body);
    }

    public function test_build_api_call_body_with_url_params(): void
    {
        $body = $this->builder->buildApiCallBody('get', '/api/users/{user}/posts', 'Post[]', ['user'], bodyIndent: 2);

        $expected = "        const response = await api.get<Post[]>(`/api/users/\${user}/posts`);\n        return response.data;\n";

        $this->assertSame($expected, $body);
    }

    public function test_build_api_call_body_inline_without_generic(): void
    {
        $body = $this->builder->buildApiCallBody('delete', '/api/users/{id}', 'void', ['id'], bodyIndent: 2);

        $expected = "        const response = await api.delete<void>(`/api/users/\${id}`);\n        return response.data;\n";

        $this->assertSame($expected, $body);
    }

    public function test_build_api_call_body_null_response_type(): void
    {
        $body = $this->builder->buildApiCallBody('get', '/api/users', null, bodyIndent: 2);

        $expected = "        const response = await api.get('/api/users');\n";

        $this->assertSame($expected, $body);
    }
}
