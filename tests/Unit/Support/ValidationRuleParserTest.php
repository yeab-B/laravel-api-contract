<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Support\ValidationRuleParser;

class ValidationRuleParserTest extends TestCase
{
    private ValidationRuleParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ValidationRuleParser();
    }

    public function test_parses_string_rules(): void
    {
        $result = $this->parser->parse('email', 'required|email');

        $this->assertSame('email', $result['type']);
        $this->assertTrue($result['required']);
        $this->assertSame(['required', 'email'], $result['rules']);
    }

    public function test_parses_array_rules(): void
    {
        $result = $this->parser->parse('name', ['required', 'string', 'max:255']);

        $this->assertSame('string', $result['type']);
        $this->assertTrue($result['required']);
        $this->assertSame(['required', 'string', 'max:255'], $result['rules']);
    }

    public function test_detects_optional_field(): void
    {
        $result = $this->parser->parse('bio', 'nullable|string');

        $this->assertFalse($result['required']);
        $this->assertSame('string', $result['type']);
    }

    public function test_detects_integer_type(): void
    {
        $result = $this->parser->parse('age', 'nullable|integer');

        $this->assertSame('integer', $result['type']);
        $this->assertFalse($result['required']);
    }

    public function test_detects_boolean_type(): void
    {
        $result = $this->parser->parse('active', 'boolean');

        $this->assertSame('boolean', $result['type']);
    }

    public function test_detects_numeric_type(): void
    {
        $result = $this->parser->parse('price', 'required|numeric');

        $this->assertSame('numeric', $result['type']);
        $this->assertTrue($result['required']);
    }

    public function test_detects_date_type(): void
    {
        $result = $this->parser->parse('birthday', 'required|date');

        $this->assertSame('date', $result['type']);
    }

    public function test_detects_array_type(): void
    {
        $result = $this->parser->parse('items', 'required|array');

        $this->assertSame('array', $result['type']);
    }

    public function test_returns_mixed_for_no_type_rule(): void
    {
        $result = $this->parser->parse('slug', 'required|unique:posts,slug');

        $this->assertSame('mixed', $result['type']);
        $this->assertTrue($result['required']);
    }

    public function test_normalize_string_rules(): void
    {
        $rules = $this->parser->normalizeRules('required|string|max:255');

        $this->assertSame(['required', 'string', 'max:255'], $rules);
    }

    public function test_normalize_array_rules(): void
    {
        $rules = $this->parser->normalizeRules(['required', 'string', 'max:255']);

        $this->assertSame(['required', 'string', 'max:255'], $rules);
    }

    public function test_handles_rule_with_parameters(): void
    {
        $result = $this->parser->parse('password', 'required|string|min:8|max:255');

        $this->assertSame('string', $result['type']);
        $this->assertTrue($result['required']);
        $this->assertContains('min:8', $result['rules']);
        $this->assertContains('max:255', $result['rules']);
    }

    public function test_type_priority(): void
    {
        $result = $this->parser->parse('amount', 'integer|string');

        $this->assertSame('integer', $result['type']);
    }
}
