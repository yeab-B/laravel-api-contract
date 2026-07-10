<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Test;

use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class TestBuilder
{
    private const INDENT = '    ';

    private string $output = '';

    private int $indentLevel = 0;

    public function reset(): void
    {
        $this->output = '';
        $this->indentLevel = 0;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function line(string $line = ''): self
    {
        if ($line === '') {
            $this->output .= "\n";

            return $this;
        }

        $this->output .= str_repeat(self::INDENT, $this->indentLevel) . $line . "\n";

        return $this;
    }

    public function indent(): self
    {
        $this->indentLevel++;

        return $this;
    }

    public function outdent(): self
    {
        $this->indentLevel = max(0, $this->indentLevel - 1);

        return $this;
    }

    public function blankLine(): self
    {
        $this->output .= "\n";

        return $this;
    }

    public function writePhpTag(): self
    {
        $this->line('<?php');

        return $this;
    }

    public function writeDeclareStrict(): self
    {
        $this->line('declare(strict_types=1);');

        return $this;
    }

    public function writeNamespace(string $namespace): self
    {
        $this->line('namespace ' . $namespace . ';');

        return $this;
    }

    public function writeUse(string $class): self
    {
        $this->line('use ' . $class . ';');

        return $this;
    }

    public function openClass(string $name): self
    {
        $this->blankLine();
        $this->line('class ' . $name . ' extends TestCase');
        $this->line('{');
        $this->indent();

        return $this;
    }

    public function closeClass(): self
    {
        $this->outdent();
        $this->line('}');

        return $this;
    }

    public function openMethod(string $name): self
    {
        $this->blankLine();
        $this->line('public function ' . $name . '(): void');
        $this->line('{');
        $this->indent();

        return $this;
    }

    public function closeMethod(): self
    {
        $this->outdent();
        $this->line('}');

        return $this;
    }

    /**
     * @param array<int, ValidationField> $fields
     */
    public function writePayload(array $fields): self
    {
        $this->line('$payload = [');

        foreach ($fields as $i => $field) {
            $comma = $i < count($fields) - 1 ? ',' : '';
            $this->line("'{$field->name()}' => '{$this->exampleValue($field)}'{$comma}");
        }

        $this->line('];');

        return $this;
    }

    public function writeEmptyPayload(): self
    {
        $this->line('$payload = [];');

        return $this;
    }

    public function writeUri(string $uri): self
    {
        $this->line("\$uri = '{$uri}';");

        return $this;
    }

    public function writeGet(string $uri): self
    {
        $this->line("\$response = \$this->getJson('{$uri}');");

        return $this;
    }

    public function writePost(string $uri): self
    {
        $this->line("\$response = \$this->postJson('{$uri}', \$payload);");

        return $this;
    }

    public function writePut(string $uri): self
    {
        $this->line("\$response = \$this->putJson('{$uri}', \$payload);");

        return $this;
    }

    public function writePatch(string $uri): self
    {
        $this->line("\$response = \$this->patchJson('{$uri}', \$payload);");

        return $this;
    }

    public function writeDelete(string $uri): self
    {
        $this->line("\$response = \$this->deleteJson('{$uri}');");

        return $this;
    }

    public function writeAssertOk(): self
    {
        $this->line('$response->assertOk();');

        return $this;
    }

    public function writeAssertCreated(): self
    {
        $this->line('$response->assertCreated();');

        return $this;
    }

    public function writeAssertNoContent(): self
    {
        $this->line('$response->assertNoContent();');

        return $this;
    }

    public function writeAssertUnauthorized(): self
    {
        $this->line('$response->assertUnauthorized();');

        return $this;
    }

    public function writeAssertUnprocessable(): self
    {
        $this->line('$response->assertStatus(422);');

        return $this;
    }

    private function exampleValue(ValidationField $field): string
    {
        return match ($field->type()) {
            'email' => 'john@example.com',
            'integer', 'int', 'number' => '1',
            'boolean', 'bool' => 'true',
            default => 'example',
        };
    }
}
