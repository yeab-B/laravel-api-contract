<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Support\ResourceParser;

class ResourceParserTest extends TestCase
{
    private ResourceParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ResourceParser();
    }

    public function test_extracts_simple_array_entries(): void
    {
        $body = '
            return [
                \'id\' => $this->id,
                \'name\' => $this->name,
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);

        $this->assertCount(2, $entries);
        $this->assertSame("'id'", $entries[0]['key']);
        $this->assertSame('$this->id', $entries[0]['value']);
        $this->assertSame("'name'", $entries[1]['key']);
        $this->assertSame('$this->name', $entries[1]['value']);
    }

    public function test_parses_simple_fields(): void
    {
        $body = '
            return [
                \'id\' => $this->id,
                \'name\' => $this->name,
                \'email\' => $this->email,
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(3, $fields);

        $this->assertSame('id', $fields[0]->name());
        $this->assertSame('mixed', $fields[0]->type());
        $this->assertSame('$this->id', $fields[0]->source());

        $this->assertSame('name', $fields[1]->name());
        $this->assertSame('email', $fields[2]->name());
    }

    public function test_parses_computed_fields(): void
    {
        $body = '
            return [
                \'full_name\' => $this->first_name . \' \' . $this->last_name,
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertSame('full_name', $fields[0]->name());
        $this->assertSame('string', $fields[0]->type());
    }

    public function test_parses_boolean_literal(): void
    {
        $entry = $this->parser->parseEntry('is_admin', 'true');

        $this->assertNotNull($entry);
        $this->assertSame('boolean', $entry->type());
    }

    public function test_parses_numeric_literal(): void
    {
        $entry = $this->parser->parseEntry('count', '42');

        $this->assertNotNull($entry);
        $this->assertSame('integer', $entry->type());
    }

    public function test_parses_float_literal(): void
    {
        $entry = $this->parser->parseEntry('price', '3.14');

        $this->assertNotNull($entry);
        $this->assertSame('float', $entry->type());
    }

    public function test_parses_string_literal(): void
    {
        $entry = $this->parser->parseEntry('greeting', "'hello'");

        $this->assertNotNull($entry);
        $this->assertSame('string', $entry->type());
    }

    public function test_parses_inline_array(): void
    {
        $entry = $this->parser->parseEntry('items', "['a', 'b']");

        $this->assertNotNull($entry);
        $this->assertSame('array', $entry->type());
    }

    public function test_detects_relationship_collection(): void
    {
        $body = '
            return [
                \'posts\' => PostResource::collection($this->posts),
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertSame('PostResource', $fields[0]->type());
        $this->assertTrue($fields[0]->isRelationship());
        $this->assertTrue($fields[0]->collection());
        $this->assertSame('PostResource', $fields[0]->relationClass());
    }

    public function test_detects_relationship_make(): void
    {
        $body = '
            return [
                \'user\' => UserResource::make($this->user),
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertSame('UserResource', $fields[0]->type());
        $this->assertTrue($fields[0]->isRelationship());
        $this->assertFalse($fields[0]->collection());
    }

    public function test_detects_relationship_new(): void
    {
        $body = '
            return [
                \'profile\' => new ProfileResource($this->profile),
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertSame('ProfileResource', $fields[0]->type());
        $this->assertTrue($fields[0]->isRelationship());
        $this->assertFalse($fields[0]->collection());
    }

    public function test_detects_nullable_when_loaded(): void
    {
        $body = '
            return [
                \'profile\' => $this->whenLoaded(\'profile\'),
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertTrue($fields[0]->nullable());
    }

    public function test_detects_nullable_when(): void
    {
        $entry = $this->parser->parseEntry('name', '$this->when($this->condition, $this->name)');

        $this->assertNotNull($entry);
        $this->assertTrue($entry->nullable());
    }

    public function test_infers_type_mixed(): void
    {
        $entry = $this->parser->parseEntry('data', '$this->data');

        $this->assertNotNull($entry);
        $this->assertSame('mixed', $entry->type());
    }

    public function test_extract_resource_class(): void
    {
        $this->assertSame('UserResource', $this->parser->extractResourceClass('UserResource::collection($this->users)'));
        $this->assertSame('UserResource', $this->parser->extractResourceClass('UserResource::make($this->user)'));
        $this->assertSame('ProfileResource', $this->parser->extractResourceClass('new ProfileResource($this->profile)'));
    }

    public function test_extract_return_array_detects_array(): void
    {
        $body = '
            return [
                \'id\' => $this->id,
            ];
        ';

        $result = $this->parser->extractReturnArray($body);

        $this->assertNotNull($result);
        $this->assertStringContainsString("'id'", $result);
        $this->assertStringContainsString('$this->id', $result);
    }

    public function test_extract_return_array_returns_null_when_no_return(): void
    {
        $body = 'return null;';

        $result = $this->parser->extractReturnArray($body);

        $this->assertNull($result);
    }

    public function test_parses_full_resource(): void
    {
        $body = '
            return [
                \'id\' => $this->id,
                \'name\' => $this->name,
                \'email\' => $this->email,
                \'full_name\' => $this->first_name . \' \' . $this->last_name,
                \'is_admin\' => true,
                \'posts\' => PostResource::collection($this->posts),
                \'profile\' => new ProfileResource($this->profile),
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(7, $fields);

        $this->assertSame('id', $fields[0]->name());
        $this->assertSame('mixed', $fields[0]->type());

        $this->assertSame('full_name', $fields[3]->name());
        $this->assertSame('string', $fields[3]->type());

        $this->assertSame('is_admin', $fields[4]->name());
        $this->assertSame('boolean', $fields[4]->type());

        $this->assertSame('posts', $fields[5]->name());
        $this->assertTrue($fields[5]->isRelationship());
        $this->assertTrue($fields[5]->collection());

        $this->assertSame('profile', $fields[6]->name());
        $this->assertTrue($fields[6]->isRelationship());
        $this->assertFalse($fields[6]->collection());
    }

    public function test_handles_nested_array(): void
    {
        $body = '
            return [
                \'metadata\' => [
                    \'key\' => $this->key,
                ],
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertSame('metadata', $fields[0]->name());
        $this->assertSame('array', $fields[0]->type());
    }

    public function test_detects_relationship_with_fqcn(): void
    {
        $body = '
            return [
                \'posts\' => \App\Http\Resources\PostResource::collection($this->posts),
            ];
        ';

        $entries = $this->parser->extractArrayEntries($body);
        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertSame('App\Http\Resources\PostResource', $fields[0]->type());
        $this->assertSame('App\Http\Resources\PostResource', $fields[0]->relationClass());
    }

    public function test_cleans_quoted_keys(): void
    {
        $entry = $this->parser->parseEntry('user_name', '$this->name');

        $this->assertNotNull($entry);
        $this->assertSame('user_name', $entry->name());
    }

    public function test_parse_cleans_quoted_keys_from_entries(): void
    {
        $entries = [
            ['key' => "'user_name'", 'value' => '$this->name', 'raw' => '$this->name'],
        ];

        $fields = $this->parser->parse($entries);

        $this->assertCount(1, $fields);
        $this->assertSame('user_name', $fields[0]->name());
    }

    public function test_detects_relationship_via_detect_relationship(): void
    {
        $result = $this->parser->detectRelationship('PostResource::collection($this->posts)');
        $this->assertNotNull($result);
        $this->assertSame('PostResource', $result['class']);
        $this->assertTrue($result['collection']);

        $result = $this->parser->detectRelationship('UserResource::make($this->user)');
        $this->assertNotNull($result);
        $this->assertSame('UserResource', $result['class']);
        $this->assertFalse($result['collection']);

        $result = $this->parser->detectRelationship('new ProfileResource($this->profile)');
        $this->assertNotNull($result);
        $this->assertSame('ProfileResource', $result['class']);
        $this->assertFalse($result['collection']);
    }
}
