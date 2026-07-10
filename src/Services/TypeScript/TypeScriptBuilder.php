<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\TypeScript;

class TypeScriptBuilder
{
    private int $indentLevel = 0;

    private string $output = '';

    private const INDENT = '    ';

    public function reset(): void
    {
        $this->indentLevel = 0;
        $this->output = '';
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

    /**
     * @param array<string, string> $properties
     */
    public function interface(string $name, array $properties, bool $export = true): self
    {
        $prefix = $export ? 'export ' : '';

        $this->line("{$prefix}interface {$name} {");
        $this->indentLevel++;

        foreach ($properties as $propertyName => $propertyType) {
            $this->line("{$propertyName}: {$propertyType};");
        }

        $this->indentLevel--;
        $this->line('}');

        return $this;
    }

    /**
     * @param array<string, string> $properties
     */
    public function requestInterface(string $name, array $properties, bool $export = true): self
    {
        $prefix = $export ? 'export ' : '';

        $this->line("{$prefix}interface {$name} {");
        $this->indentLevel++;

        foreach ($properties as $propertyName => $propertyType) {
            $this->line("{$propertyName}: {$propertyType};");
        }

        $this->indentLevel--;
        $this->line('}');

        return $this;
    }

    /**
     * @param array<string, string> $members
     */
    public function enum(string $name, array $members, bool $export = true): self
    {
        $prefix = $export ? 'export ' : '';

        $this->line("{$prefix}enum {$name} {");
        $this->indentLevel++;

        foreach ($members as $key => $value) {
            $this->line("{$key} = '{$value}',");
        }

        $this->indentLevel--;
        $this->line('}');

        return $this;
    }

    public function comment(string $text): self
    {
        $this->line("// {$text}");

        return $this;
    }

    public function blankLine(): self
    {
        $this->line('');

        return $this;
    }

    public function export(string $content): self
    {
        $this->output .= $content . "\n";

        return $this;
    }

    /**
     * Helper: format a nullable property type.
     */
    public static function nullable(string $type, bool $isNullable): string
    {
        if (!$isNullable) {
            return $type;
        }

        return "{$type} | null";
    }
}
