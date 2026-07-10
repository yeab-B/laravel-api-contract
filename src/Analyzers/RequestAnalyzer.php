<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Analyzers;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use ReflectionMethod;
use Yab\LaravelApiContract\Contracts\RequestAnalyzerContract;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Support\ValidationRuleParser;

class RequestAnalyzer implements RequestAnalyzerContract
{
    public function __construct(
        private readonly ValidationRuleParser $parser,
    ) {
    }

    public function analyze(ControllerDefinition $definition): ?RequestDefinition
    {
        $requestClass = $this->findFormRequest($definition);

        if ($requestClass === null) {
            return null;
        }

        if (!class_exists($requestClass)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($requestClass);

        if (!$reflectionClass->isSubclassOf(FormRequest::class)) {
            return null;
        }

        $instance = $reflectionClass->newInstanceWithoutConstructor();

        $rawRules = $this->resolveRules($instance);
        $authorize = $this->resolveAuthorize($instance);
        $fields = $this->buildFields($rawRules);

        return new RequestDefinition(
            className: $reflectionClass->getName(),
            fields: $fields,
            authorizeMethod: $authorize,
            rawRules: $rawRules,
        );
    }

    private function findFormRequest(ControllerDefinition $definition): ?string
    {
        foreach ($definition->parameters() as $param) {
            if ($param['class'] !== null) {
                $class = $param['class'];

                if (class_exists($class) && is_subclass_of($class, FormRequest::class)) {
                    return $class;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRules(object $instance): array
    {
        if (!method_exists($instance, 'rules')) {
            return [];
        }

        $reflectionMethod = new ReflectionMethod($instance, 'rules');

        if (!$reflectionMethod->isPublic()) {
            return [];
        }

        $rules = $reflectionMethod->invoke($instance);

        return is_array($rules) ? $rules : [];
    }

    private function resolveAuthorize(object $instance): bool
    {
        if (!method_exists($instance, 'authorize')) {
            return true;
        }

        $reflectionMethod = new ReflectionMethod($instance, 'authorize');

        if (!$reflectionMethod->isPublic()) {
            return true;
        }

        $result = $reflectionMethod->invoke($instance);

        return $result !== false;
    }

    /**
     * @param array<string, mixed> $rawRules
     * @return array<int, ValidationField>
     */
    private function buildFields(array $rawRules): array
    {
        $fields = [];

        foreach ($rawRules as $fieldName => $rules) {
            if (str_contains($fieldName, '*')) {
                continue;
            }

            if (!is_string($rules) && !is_array($rules)) {
                continue;
            }

            $parsed = $this->parser->parse($fieldName, $rules);

            $fields[] = new ValidationField(
                name: $fieldName,
                type: $parsed['type'],
                required: $parsed['required'],
                rules: $parsed['rules'],
            );
        }

        return $fields;
    }
}
