<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Analyzers;

use ReflectionClass;
use ReflectionMethod;
use Yab\LaravelApiContract\Contracts\ResourceAnalyzerContract;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Support\ResourceParser;

class ResourceAnalyzer implements ResourceAnalyzerContract
{
    private const JSON_RESOURCE = 'Illuminate\Http\Resources\Json\JsonResource';

    /**
     * @var array<string, array<int, string>|null>
     */
    private array $fileCache = [];

    public function __construct(
        private readonly ResourceParser $parser,
    ) {
    }

    public function analyze(ControllerDefinition $definition): ?ResourceDefinition
    {
        $resourceClass = $this->detectResourceInController($definition);

        if ($resourceClass === null) {
            return null;
        }

        return $this->analyzeResource($resourceClass);
    }

    public function analyzeResource(string $resourceClass): ?ResourceDefinition
    {
        if (!class_exists($resourceClass)) {
            return null;
        }

        $reflection = new ReflectionClass($resourceClass);

        if (!$reflection->isSubclassOf(self::JSON_RESOURCE)) {
            return null;
        }

        if (!$reflection->hasMethod('toArray')) {
            return null;
        }

        $method = $reflection->getMethod('toArray');
        $methodBody = $this->getMethodBody($method);

        if ($methodBody === null) {
            return null;
        }

        $entries = $this->parser->extractArrayEntries($methodBody);
        $fields = $this->parser->parse($entries);

        $relationships = $this->extractRelationships($fields);

        $parentClass = $reflection->getParentClass();

        $metadata = [
            'file' => $reflection->getFileName(),
            'abstract' => $reflection->isAbstract(),
            'parent' => $parentClass instanceof \ReflectionClass ? $parentClass->getName() : null,
        ];

        return new ResourceDefinition(
            resourceClass: $reflection->getName(),
            fields: $fields,
            relationships: $relationships,
            collection: false,
            metadata: $metadata,
        );
    }

    /**
     * Detect the resource class used in a controller method by reading its source.
     */
    private function detectResourceInController(ControllerDefinition $definition): ?string
    {
        $className = $definition->className();
        $methodName = $definition->method();

        if (!class_exists($className)) {
            return null;
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->hasMethod($methodName)) {
            return null;
        }

        $filename = $reflection->getFileName();

        if ($filename === false) {
            return null;
        }

        $source = $this->getFileLines($filename);

        if ($source === null) {
            return null;
        }

        $method = $reflection->getMethod($methodName);
        $namespace = $reflection->getNamespaceName();
        $useStatements = $this->extractUseStatements($source);

        $methodBody = $this->getMethodBody($method);

        if ($methodBody === null) {
            return null;
        }

        return $this->findResourceCallInBody($methodBody, $namespace, $useStatements);
    }

    /**
     * Extract the toArray() method body from a ReflectionMethod.
     */
    private function getMethodBody(ReflectionMethod $method): ?string
    {
        $filename = $method->getFileName();

        if ($filename === false) {
            return null;
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($startLine === false || $endLine === false) {
            return null;
        }

        $source = $this->getFileLines($filename);

        if ($source === null) {
            return null;
        }

        $lines = array_slice($source, $startLine - 1, $endLine - $startLine + 1);

        $body = implode("\n", $lines);

        $body = (string) preg_replace('/^.*function\s+toArray\s*\([^)]*\)\s*(?::\s*[^{]+)?\s*\{/', '', $body);
        $body = (string) preg_replace('/\}\s*$/', '', $body);

        return trim($body);
    }

    /**
     * Find a Resource call (::make, ::collection, or new XxxResource) in a method body.
     *
     * @param array<string, string> $useStatements
     */
    private function findResourceCallInBody(string $body, string $namespace, array $useStatements): ?string
    {
        $patterns = [
            '/return\s+(?:new\s+)?(\w+(?:\\\\\w+)*)\s*::\s*make\s*\(/s',
            '/return\s+(?:new\s+)?(\w+(?:\\\\\w+)*)\s*::\s*collection\s*\(/s',
            '/return\s+new\s+(\w+(?:\\\\\w+)*)\s*\(/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $class = $matches[1];

                return $this->resolveClass($class, $namespace, $useStatements);
            }
        }

        return null;
    }

    /**
     * Extract use statements from source file lines.
     *
     * @param array<int, string> $sourceLines
     *
     * @return array<string, string>
     */
    private function extractUseStatements(array $sourceLines): array
    {
        $imports = [];

        foreach ($sourceLines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^use\s+((\w+(?:\\\\\w+)*)\s+as\s+(\w+)|(\w+(?:\\\\\w+)*))\s*;$/', $trimmed, $matches)) {
                if (!empty($matches[2]) && !empty($matches[3])) {
                    $imports[$matches[3]] = $matches[2];
                } elseif (!empty($matches[4])) {
                    $parts = explode('\\', $matches[4]);
                    $shortName = end($parts);
                    $imports[$shortName] = $matches[4];
                }
            }
        }

        return $imports;
    }

    /**
     * Resolve a short class name to a fully qualified class name.
     *
     * @param array<string, string> $imports
     */
    private function resolveClass(string $class, string $namespace, array $imports): string
    {
        if (str_contains($class, '\\')) {
            return $class;
        }

        if (isset($imports[$class])) {
            return $imports[$class];
        }

        return $namespace . '\\' . $class;
    }

    /**
     * @param array<int, ResponseField> $fields
     *
     * @return array<int, string>
     */
    private function extractRelationships(array $fields): array
    {
        $relationships = [];

        foreach ($fields as $field) {
            if ($field->isRelationship() && $field->relationClass() !== null) {
                $relationships[] = $field->relationClass();
            }
        }

        /** @var array<int, string> $unique */
        $unique = array_values(array_unique($relationships));

        return $unique;
    }

    /**
     * Get and cache file lines from disk.
     *
     * @return array<int, string>|null
     */
    private function getFileLines(string $filename): ?array
    {
        if (!array_key_exists($filename, $this->fileCache)) {
            if (!file_exists($filename) || !is_readable($filename)) {
                $this->fileCache[$filename] = null;
            } else {
                $lines = file($filename, FILE_IGNORE_NEW_LINES);
                $this->fileCache[$filename] = $lines !== false ? $lines : null;
            }
        }

        return $this->fileCache[$filename];
    }
}
