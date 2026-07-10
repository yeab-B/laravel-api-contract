<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Client;

class ClientBuilder
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

    /**
     * @param array<int, string> $named
     */
    public function namedImport(array $named, string $path, bool $type = false): self
    {
        $typeKeyword = $type ? 'type ' : '';
        $names = implode(', ', $named);

        $this->line("import {$typeKeyword}{ {$names} } from '{$path}';");

        return $this;
    }

    /**
     * @param array<int, string> $functionLines
     */
    public function serviceObject(string $name, array $functionLines): self
    {
        $this->blankLine();
        $this->line("export const {$name} = {");
        $this->indent();

        foreach ($functionLines as $fn) {
            $this->output .= $fn;
        }

        $this->outdent();
        $this->line('};');

        return $this;
    }

    /**
     * Build a full async function block as a string.
     *
     * @param array<int, string> $params  e.g. ["id: number", "data: CreateUserRequest"]
     * @param int               $baseIndent  The indent level for the function line (inside service object = 1)
     */
    public function buildAsyncFunction(
        string $name,
        array $params,
        string $returnType,
        string $body,
        int $baseIndent = 1,
    ): string {
        $paramsStr = $params !== [] ? implode(', ', $params) : '';
        $indent = str_repeat(self::INDENT, $baseIndent);

        return "{$indent}{$name}: async ({$paramsStr}): Promise<{$returnType}> => {\n{$body}{$indent}},\n";
    }

    /**
     * Build the body of an API call.
     *
     * @param array<int, string> $urlParams  e.g. ["id"]
     * @param int               $bodyIndent  The indent level for the body (inside function = 2)
     */
    public function buildApiCallBody(
        string $httpMethod,
        string $path,
        ?string $responseType,
        array $urlParams = [],
        ?string $requestParam = null,
        int $bodyIndent = 2,
    ): string {
        $indent = str_repeat(self::INDENT, $bodyIndent);
        $body = '';

        $interpolatedPath = $this->interpolatePath($path, $urlParams);
        $genericType = $responseType !== null ? "<{$responseType}>" : '';

        if ($requestParam !== null) {
            $body .= "{$indent}const response = await api.{$httpMethod}{$genericType}(" .
                     "{$interpolatedPath}, {$requestParam});\n";
        } else {
            $body .= "{$indent}const response = await api.{$httpMethod}{$genericType}({$interpolatedPath});\n";
        }

        if ($responseType !== null) {
            $body .= "{$indent}return response.data;\n";
        }

        return $body;
    }

    /**
     * Interpolate path parameters into a JavaScript template literal.
     *
     * @param array<int, string> $urlParams
     */
    private function interpolatePath(string $path, array $urlParams): string
    {
        if ($urlParams === []) {
            return "'{$path}'";
        }

        $interpolated = $path;

        foreach ($urlParams as $param) {
            $interpolated = str_replace('{' . $param . '}', '${' . $param . '}', $interpolated);
        }

        return "`{$interpolated}`";
    }
}
