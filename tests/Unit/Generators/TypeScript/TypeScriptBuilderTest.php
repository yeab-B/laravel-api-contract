<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators\TypeScript;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\TypeScript\TypeScriptBuilder;

class TypeScriptBuilderTest extends TestCase
{
    private TypeScriptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new TypeScriptBuilder();
    }

    public function test_interface_basic(): void
    {
        $this->builder->interface('User', [
            'id' => 'number',
            'name' => 'string',
            'email' => 'string',
        ]);

        $expected = <<<'TS'
export interface User {
    id: number;
    name: string;
    email: string;
}

TS;

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_interface_without_export(): void
    {
        $this->builder->interface('User', [
            'id' => 'number',
        ], false);

        $expected = <<<'TS'
interface User {
    id: number;
}

TS;

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_request_interface(): void
    {
        $this->builder->requestInterface('CreateUser', [
            'name' => 'string',
            'email' => 'string',
        ]);

        $expected = <<<'TS'
export interface CreateUser {
    name: string;
    email: string;
}

TS;

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_enum(): void
    {
        $this->builder->enum('UserStatus', [
            'ACTIVE' => 'active',
            'INACTIVE' => 'inactive',
        ]);

        $expected = <<<'TS'
export enum UserStatus {
    ACTIVE = 'active',
    INACTIVE = 'inactive',
}

TS;

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_comment(): void
    {
        $this->builder->comment('Auto-generated file');

        $expected = "// Auto-generated file\n";

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_blank_line(): void
    {
        $this->builder->line('hello');
        $this->builder->blankLine();
        $this->builder->line('world');

        $expected = "hello\n\nworld\n";

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_reset(): void
    {
        $this->builder->line('hello');
        $this->builder->reset();
        $this->builder->line('world');

        $this->assertSame("world\n", $this->builder->getOutput());
    }

    public function test_nullable_helper_returns_type_when_not_nullable(): void
    {
        $this->assertSame('string', TypeScriptBuilder::nullable('string', false));
    }

    public function test_nullable_helper_returns_union_when_nullable(): void
    {
        $this->assertSame('string | null', TypeScriptBuilder::nullable('string', true));
    }

    public function test_multiple_interfaces_separated_by_blanks(): void
    {
        $this->builder->interface('User', ['id' => 'number']);
        $this->builder->blankLine();
        $this->builder->interface('Post', ['title' => 'string']);

        $expected = <<<'TS'
export interface User {
    id: number;
}

export interface Post {
    title: string;
}

TS;

        $this->assertSame($expected, $this->builder->getOutput());
    }

    public function test_interface_with_nullable_property(): void
    {
        $this->builder->interface('Profile', [
            'bio' => 'string | null',
        ]);

        $expected = <<<'TS'
export interface Profile {
    bio: string | null;
}

TS;

        $this->assertSame($expected, $this->builder->getOutput());
    }
}
