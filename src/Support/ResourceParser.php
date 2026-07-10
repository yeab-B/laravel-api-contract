<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Support;

use Yab\LaravelApiContract\Services\DTO\ResponseField;

class ResourceParser
{
    /**
     * Parse the return array from a resource toArray() method body.
     *
     * @return array<int, array{key: string|null, value: string, raw: string}>
     */
    public function extractArrayEntries(string $methodBody): array
    {
        $tokens = token_get_all('<?php ' . $methodBody);
        $entries = [];
        $arrayDepth = 0;
        $parenDepth = 0;
        $inReturnArray = false;
        $currentEntry = '';
        $currentKey = null;
        $sawReturn = false;

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                $tokenValue = $token[1];
                $tokenType = $token[0];
            } else {
                $tokenValue = $token;
                $tokenType = null;
            }

            if (!$inReturnArray) {
                if ($tokenType === T_RETURN) {
                    $sawReturn = true;
                }

                if ($sawReturn && $tokenValue === '[') {
                    $inReturnArray = true;
                    $arrayDepth = 1;
                    continue;
                }

                if ($sawReturn && $tokenType === T_ARRAY) {
                    $nextIdx = $this->findNextMeaningful($tokens, $i);
                    if ($nextIdx !== null) {
                        $nextToken = $tokens[$nextIdx];
                        if (!is_array($nextToken) && $nextToken === '(') {
                            $inReturnArray = true;
                            $arrayDepth = 1;
                        }
                    }
                }

                continue;
            }

            if ($tokenType === T_WHITESPACE || $tokenType === T_COMMENT || $tokenType === T_DOC_COMMENT) {
                $currentEntry .= $tokenValue;
                continue;
            }

            if ($tokenValue === '[') {
                $arrayDepth++;

                if ($arrayDepth > 1) {
                    $currentEntry .= '['; // nested array bracket
                }

                continue;
            }

            if ($tokenValue === ']') {
                if ($arrayDepth > 1) {
                    $currentEntry .= ']'; // nested array bracket
                }

                $arrayDepth--;

                if ($arrayDepth === 0) {
                    if ($currentEntry !== '' || $currentKey !== null) {
                        $entries[] = $this->makeEntry($currentKey, $currentEntry);
                    }
                    break;
                }

                continue;
            }

            if ($tokenValue === '(') {
                $parenDepth++;
                $currentEntry .= $tokenValue;
                continue;
            }

            if ($tokenValue === ')') {
                $parenDepth--;
                $currentEntry .= $tokenValue;
                continue;
            }

            if ($tokenValue === ',' && $arrayDepth === 1 && $parenDepth === 0) {
                if ($currentKey !== null || $currentEntry !== '') {
                    $entries[] = $this->makeEntry($currentKey, $currentEntry);
                }
                $currentKey = null;
                $currentEntry = '';
                continue;
            }

            if ($tokenType === T_DOUBLE_ARROW && $arrayDepth === 1 && $parenDepth === 0) {
                $currentKey = trim($currentEntry);
                $currentEntry = '';
                continue;
            }

            $currentEntry .= $tokenValue;
        }

        return $this->cleanEntries($entries);
    }

    /**
     * Convert raw array entries into ResponseField DTOs.
     *
     * @param array<int, array{key: string|null, value: string, raw: string}> $entries
     *
     * @return array<int, ResponseField>
     */
    public function parse(array $entries): array
    {
        $fields = [];

        foreach ($entries as $entry) {
            $key = $entry['key'];
            $value = trim($entry['value']);

            if ($key === null) {
                continue;
            }

            $cleanKey = $this->cleanKey($key);

            if ($cleanKey === null) {
                continue;
            }

            $field = $this->parseEntry($cleanKey, $value);

            if ($field !== null) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Parse a single array entry into a ResponseField.
     */
    public function parseEntry(string $fieldName, string $value): ?ResponseField
    {
        $type = $this->inferType($value);
        $nullable = $this->detectNullable($value);
        $source = $this->extractSource($value);
        $relationClass = null;
        $collection = false;

        if ($type === 'relationship') {
            $relationInfo = $this->detectRelationship($value);
            if ($relationInfo !== null) {
                $relationClass = $relationInfo['class'];
                $collection = $relationInfo['collection'];
                $type = $relationClass;
            }
        }

        return new ResponseField(
            name: $fieldName,
            type: $type,
            nullable: $nullable,
            source: $source,
            relationClass: $relationClass,
            collection: $collection,
        );
    }

    /**
     * Infer the type of a value expression.
     */
    public function inferType(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === 'true' || $trimmed === 'false') {
            return 'boolean';
        }

        if ($trimmed === 'null') {
            return 'null';
        }

        if (preg_match('/^[+-]?\d+$/', $trimmed)) {
            return 'integer';
        }

        if (preg_match('/^[+-]?\d+\.\d+$/', $trimmed)) {
            return 'float';
        }

        if (
            preg_match('/^\'[^\']*\'$/', $trimmed)
            || preg_match('/^"[^"]*"$/', $trimmed)
        ) {
            return 'string';
        }

        if (str_starts_with($trimmed, '[')) {
            return 'array';
        }

        if (
            str_contains($trimmed, '::collection')
            || str_contains($trimmed, '::make')
        ) {
            return 'relationship';
        }

        if (str_starts_with($trimmed, 'new ')) {
            return 'relationship';
        }

        if (
            str_contains($trimmed, '$this->when(')
            || str_contains($trimmed, '$this->whenLoaded(')
            || str_contains($trimmed, '$this->whenNotNull(')
        ) {
            if (str_contains($trimmed, 'new ')) {
                return 'relationship';
            }

            return 'mixed';
        }

        if (str_contains($trimmed, '$this->merge(')) {
            return 'array';
        }

        if (str_contains($trimmed, '.')) {
            return 'string';
        }

        if (preg_match('/^\(int\)/', $trimmed)) {
            return 'integer';
        }

        if (preg_match('/^\(float\)/', $trimmed) || preg_match('/^\(double\)/', $trimmed)) {
            return 'float';
        }

        if (preg_match('/^\(bool\)/', $trimmed) || preg_match('/^\(boolean\)/', $trimmed)) {
            return 'boolean';
        }

        if (preg_match('/^\(string\)/', $trimmed)) {
            return 'string';
        }

        if (preg_match('/^\(array\)/', $trimmed)) {
            return 'array';
        }

        return 'mixed';
    }

    /**
     * @return array{class: string, collection: bool}|null
     */
    public function detectRelationship(string $value): ?array
    {
        $trimmed = trim($value);

        if (preg_match('/^(\w+(?:\\\\\w+)*)::collection\(/', $trimmed, $matches)) {
            return ['class' => $matches[1], 'collection' => true];
        }

        if (preg_match('/^(\w+(?:\\\\\w+)*)::make\(/', $trimmed, $matches)) {
            return ['class' => $matches[1], 'collection' => false];
        }

        if (preg_match('/^new\s+(\w+(?:\\\\\w+)*)\(/', $trimmed, $matches)) {
            return ['class' => $matches[1], 'collection' => false];
        }

        if (str_contains($trimmed, '::collection')) {
            $parts = explode('::collection', $trimmed, 2);
            $class = trim($parts[0]);
            if (preg_match('/\w+(?:\\\\\w+)*/', $class, $classMatches)) {
                return ['class' => $classMatches[0], 'collection' => true];
            }
        }

        return null;
    }

    /**
     * Check if a value expression is nullable (wrapped in whenLoaded or similar).
     */
    public function detectNullable(string $value): bool
    {
        $trimmed = trim($value);

        if (str_contains($trimmed, '$this->whenLoaded(')) {
            return true;
        }

        if (str_contains($trimmed, '$this->whenNotNull(')) {
            return true;
        }

        if (str_contains($trimmed, '$this->when(')) {
            return true;
        }

        if (str_contains($trimmed, '$this->whenHas(')) {
            return true;
        }

        return false;
    }

    /**
     * Extract a human-readable source from a value expression.
     */
    public function extractSource(string $value): ?string
    {
        $trimmed = trim($value);

        if (preg_match('/\$this->(\w+)/', $trimmed, $matches)) {
            return '$this->' . $matches[1];
        }

        if (preg_match('/\$request->(\w+)/', $trimmed, $matches)) {
            return '$request->' . $matches[1];
        }

        return null;
    }

    /**
     * Extract the resource class name from a resource call expression.
     */
    public function extractResourceClass(string $expression): ?string
    {
        $trimmed = trim($expression);

        if (
            preg_match(
                '/^(?:new\s+)?(\w+(?:\\\\\w+)*)\s*(?:::make|::collection|::\w+\s*\(|[\s(])/',
                $trimmed,
                $matches,
            )
        ) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract the return array from the toArray method body as a string.
     */
    public function extractReturnArray(string $methodBody): ?string
    {
        $tokens = token_get_all('<?php ' . $methodBody);
        $depth = 0;
        $result = '';
        $capture = false;
        $foundReturn = false;

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                $tokenValue = $token[1];
                $tokenType = $token[0];
            } else {
                $tokenValue = $token;
                $tokenType = null;
            }

            if (!$capture) {
                if ($tokenType === T_RETURN) {
                    $foundReturn = true;
                }

                if ($foundReturn && $tokenValue === '[') {
                    $capture = true;
                    $depth = 1;
                    $result .= '[';
                    continue;
                }

                if ($foundReturn && $tokenType === T_ARRAY) {
                    $nextIdx = $this->findNextMeaningful($tokens, $i);
                    if ($nextIdx !== null) {
                        $nextToken = $tokens[$nextIdx];
                        if (!is_array($nextToken) && $nextToken === '(') {
                            $capture = true;
                            $depth = 1;
                            $result .= '(';
                        }
                    }
                }

                continue;
            }

            if ($tokenValue === '[' || $tokenValue === '(') {
                $depth++;
                $result .= $tokenValue;
                continue;
            }

            if ($tokenValue === ']' || $tokenValue === ')') {
                $depth--;
                $result .= $tokenValue;
                if ($depth === 0) {
                    break;
                }
                continue;
            }

            $result .= $tokenValue;
        }

        if (!$capture) {
            return null;
        }

        return $result;
    }

    /**
     * Clean quoted key strings.
     */
    public function cleanKey(string $key): ?string
    {
        $trimmed = trim($key);

        if ($trimmed === '') {
            return null;
        }

        if (
            (str_starts_with($trimmed, "'") && str_ends_with($trimmed, "'"))
            || (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"'))
        ) {
            return substr($trimmed, 1, -1);
        }

        if (ctype_digit($trimmed)) {
            return $trimmed;
        }

        return $trimmed;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function findNextMeaningful(array $tokens, int $current): ?int
    {
        for ($j = $current + 1; $j < count($tokens); $j++) {
            $t = $tokens[$j];
            if (is_array($t) && ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
                continue;
            }
            return $j;
        }

        return null;
    }

    /**
     * @param array<int, array{key: string|null, value: string, raw: string}> $entries
     *
     * @return array<int, array{key: string|null, value: string, raw: string}>
     */
    private function cleanEntries(array $entries): array
    {
        $cleaned = [];

        foreach ($entries as $entry) {
            $key = $entry['key'] !== null ? trim($entry['key']) : null;
            $value = trim($entry['value']);

            if ($value === '' && $key === null) {
                continue;
            }

            $cleaned[] = [
                'key' => $key,
                'value' => $value,
                'raw' => $entry['raw'],
            ];
        }

        return $cleaned;
    }

    /**
     * @return array{key: string|null, value: string, raw: string}
     */
    private function makeEntry(?string $key, string $value): array
    {
        return [
            'key' => $key,
            'value' => $value,
            'raw' => $value,
        ];
    }
}
