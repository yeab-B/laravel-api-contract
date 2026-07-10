<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Analyzers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\RouteDefinition;

class ControllerAnalyzer implements ControllerAnalyzerContract
{
    public function analyze(RouteDefinition $route): ?ControllerDefinition
    {
        $controller = $route->controller();

        if ($controller === null) {
            return null;
        }

        $parsed = $this->parseControllerAction($controller);

        if ($parsed === null) {
            return null;
        }

        [$className, $methodName] = $parsed;

        if (!class_exists($className)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->hasMethod($methodName)) {
            $methodName = '__invoke';

            if (!$reflectionClass->hasMethod($methodName)) {
                return null;
            }
        }

        $reflectionMethod = $reflectionClass->getMethod($methodName);

        $visibility = $this->resolveVisibility($reflectionMethod);
        $parameters = $this->resolveParameters($reflectionMethod);
        $returnType = $this->resolveReturnType($reflectionMethod);
        $dependencies = $this->resolveDependencies($parameters);

        return new ControllerDefinition(
            className: $reflectionClass->getName(),
            method: $reflectionMethod->getName(),
            visibility: $visibility,
            parameters: $parameters,
            returnType: $returnType,
            dependencies: $dependencies,
        );
    }

    /**
     * @return array{string, string}|null
     */
    private function parseControllerAction(string $controller): ?array
    {
        if (str_contains($controller, '@')) {
            $parts = explode('@', $controller, 2);

            return [$parts[0], $parts[1]];
        }

        if (class_exists($controller)) {
            return [$controller, '__invoke'];
        }

        return null;
    }

    private function resolveVisibility(ReflectionMethod $method): string
    {
        if ($method->isPublic()) {
            return 'public';
        }

        if ($method->isProtected()) {
            return 'protected';
        }

        return 'private';
    }

    /**
     * @return array<int, array{name: string, type: ?string, class: ?string}>
     */
    private function resolveParameters(ReflectionMethod $method): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $param) {
            $type = null;
            $class = null;

            $reflectionType = $param->getType();

            if ($reflectionType instanceof ReflectionNamedType) {
                $type = $reflectionType->getName();

                if (!$reflectionType->isBuiltin()) {
                    $class = $type;
                }
            }

            $parameters[] = [
                'name' => $param->getName(),
                'type' => $type,
                'class' => $class,
            ];
        }

        return $parameters;
    }

    private function resolveReturnType(ReflectionMethod $method): ?string
    {
        $reflectionType = $method->getReturnType();

        if ($reflectionType instanceof ReflectionNamedType) {
            return $reflectionType->getName();
        }

        return null;
    }

    /**
     * @param array<int, array{name: string, type: ?string, class: ?string}> $parameters
     * @return array<int, string>
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $param) {
            if ($param['class'] !== null) {
                $dependencies[] = $param['class'];
            }
        }

        return array_values(array_unique($dependencies));
    }
}
