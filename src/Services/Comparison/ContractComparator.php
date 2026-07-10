<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Comparison;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Contracts\ContractComparatorContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class ContractComparator implements ContractComparatorContract
{
    public function compare(ApiContractContract $old, ApiContractContract $new): ChangeReport
    {
        $changes = [];

        $this->compareAuthentication($old, $new, $changes);
        $this->compareEndpoints($old, $new, $changes);

        return new ChangeReport($old->version(), $new->version(), $changes);
    }

    /**
     * @param array<int, ApiChange> $changes
     */
    private function compareAuthentication(ApiContractContract $old, ApiContractContract $new, array &$changes): void
    {
        if ($old->authentication() !== $new->authentication()) {
            $changes[] = new ApiChange(
                ApiChange::CHANGED_AUTH,
                'global',
                "Authentication method changed from '{$old->authentication()}' to '{$new->authentication()}'.",
            );
        }
    }

    /**
     * @param array<int, ApiChange> $changes
     */
    private function compareEndpoints(ApiContractContract $old, ApiContractContract $new, array &$changes): void
    {
        /** @var array<string, EndpointDefinition> $oldEndpoints */
        $oldEndpoints = [];
        foreach ($old->endpoints() as $endpoint) {
            $oldEndpoints[$endpoint->key()] = $endpoint;
        }

        /** @var array<string, EndpointDefinition> $newEndpoints */
        $newEndpoints = [];
        foreach ($new->endpoints() as $endpoint) {
            $newEndpoints[$endpoint->key()] = $endpoint;
        }

        $oldKeys = array_keys($oldEndpoints);
        $newKeys = array_keys($newEndpoints);

        // Exact matches (matching HTTP method and URI key)
        $exactMatches = array_intersect($oldKeys, $newKeys);

        // Keys that are not in exact matches
        $unmatchedOldKeys = array_diff($oldKeys, $exactMatches);
        $unmatchedNewKeys = array_diff($newKeys, $exactMatches);

        $pairedOldKeys = [];
        $pairedNewKeys = [];

        // Pair unmatched endpoints by name or normalized URI to detect CHANGED_METHOD
        foreach ($unmatchedOldKeys as $oldKey) {
            $oldEndpoint = $oldEndpoints[$oldKey];

            foreach ($unmatchedNewKeys as $newKey) {
                if (in_array($newKey, $pairedNewKeys, true)) {
                    continue;
                }

                $newEndpoint = $newEndpoints[$newKey];

                $isMatch = false;
                if ($oldEndpoint->name() !== null && $newEndpoint->name() !== null) {
                    $isMatch = $oldEndpoint->name() === $newEndpoint->name();
                }

                if (!$isMatch) {
                    $isMatch = $this->normalizeUri($oldEndpoint->uri()) === $this->normalizeUri($newEndpoint->uri());
                }

                if ($isMatch) {
                    $pairedOldKeys[] = $oldKey;
                    $pairedNewKeys[] = $newKey;

                    $changes[] = new ApiChange(
                        ApiChange::CHANGED_METHOD,
                        $newKey,
                        "HTTP method changed from '{$oldEndpoint->method()}' to '{$newEndpoint->method()}'.",
                    );

                    $this->compareEndpointDetails($oldEndpoint, $newEndpoint, $changes);
                    break;
                }
            }
        }

        // Truly removed endpoints
        foreach ($unmatchedOldKeys as $oldKey) {
            if (in_array($oldKey, $pairedOldKeys, true)) {
                continue;
            }
            $changes[] = new ApiChange(
                ApiChange::REMOVED_ENDPOINT,
                $oldKey,
                "Endpoint '{$oldKey}' has been removed.",
            );
        }

        // Truly added endpoints
        foreach ($unmatchedNewKeys as $newKey) {
            if (in_array($newKey, $pairedNewKeys, true)) {
                continue;
            }
            $changes[] = new ApiChange(
                ApiChange::ADDED_ENDPOINT,
                $newKey,
                "Endpoint '{$newKey}' has been added.",
            );
        }

        // Exact matches
        foreach ($exactMatches as $key) {
            $this->compareEndpointDetails($oldEndpoints[$key], $newEndpoints[$key], $changes);
        }
    }

    /**
     * @param array<int, ApiChange> $changes
     */
    private function compareEndpointDetails(EndpointDefinition $old, EndpointDefinition $new, array &$changes): void
    {
        $key = $new->key();

        // Compare request fields
        $oldRequest = $old->request();
        $newRequest = $new->request();

        if ($oldRequest !== null && $newRequest !== null) {
            $this->compareRequestFields($key, $oldRequest, $newRequest, $changes);
        } elseif ($oldRequest === null && $newRequest !== null) {
            // Check if any of the new request fields are required. If so, adding request schema is breaking.
            $hasRequired = false;
            foreach ($newRequest->fields() as $field) {
                if ($field->required()) {
                    $hasRequired = true;
                    break;
                }
            }

            $changes[] = new ApiChange(
                ApiChange::ADDED_REQUEST_FIELD,
                $key,
                "Endpoint '{$key}' now has a request definition.",
                $hasRequired ? ApiChange::BREAKING : ApiChange::NON_BREAKING,
            );
        } elseif ($oldRequest !== null && $newRequest === null) {
            $changes[] = new ApiChange(
                ApiChange::REMOVED_REQUEST_FIELD,
                $key,
                "Endpoint '{$key}' no longer has a request definition.",
            );
        }

        // Compare response fields
        $oldResponse = $old->response();
        $newResponse = $new->response();

        if ($oldResponse !== null && $newResponse !== null) {
            $this->compareResponseFields($key, $oldResponse, $newResponse, $changes);
        } elseif ($oldResponse === null && $newResponse !== null) {
            $changes[] = new ApiChange(
                ApiChange::ADDED_RESPONSE_FIELD,
                $key,
                "Endpoint '{$key}' now has a response definition.",
            );
        } elseif ($oldResponse !== null && $newResponse === null) {
            $changes[] = new ApiChange(
                ApiChange::REMOVED_RESPONSE_FIELD,
                $key,
                "Endpoint '{$key}' no longer has a response definition.",
            );
        }
    }

    /**
     * @param array<int, ApiChange> $changes
     */
    private function compareRequestFields(
        string $endpointKey,
        RequestDefinition $old,
        RequestDefinition $new,
        array &$changes,
    ): void {
        /** @var array<string, ValidationField> $oldFields */
        $oldFields = [];
        foreach ($old->fields() as $field) {
            $oldFields[$field->name()] = $field;
        }

        /** @var array<string, ValidationField> $newFields */
        $newFields = [];
        foreach ($new->fields() as $field) {
            $newFields[$field->name()] = $field;
        }

        $oldNames = array_keys($oldFields);
        $newNames = array_keys($newFields);

        foreach (array_diff($oldNames, $newNames) as $removedField) {
            $changes[] = new ApiChange(
                ApiChange::REMOVED_REQUEST_FIELD,
                "{$endpointKey}/request/{$removedField}",
                "Request field '{$removedField}' has been removed from '{$endpointKey}'.",
            );
        }

        foreach (array_diff($newNames, $oldNames) as $addedField) {
            $field = $newFields[$addedField];
            $changes[] = new ApiChange(
                ApiChange::ADDED_REQUEST_FIELD,
                "{$endpointKey}/request/{$addedField}",
                "Request field '{$addedField}' has been added to '{$endpointKey}'.",
                $field->required() ? ApiChange::BREAKING : ApiChange::NON_BREAKING,
            );
        }

        foreach (array_intersect($oldNames, $newNames) as $fieldName) {
            $oldField = $oldFields[$fieldName];
            $newField = $newFields[$fieldName];

            if ($oldField->type() !== $newField->type()) {
                $changes[] = new ApiChange(
                    ApiChange::CHANGED_REQUEST_FIELD_TYPE,
                    "{$endpointKey}/request/{$fieldName}",
                    "Request field '{$fieldName}' type changed from '{$oldField->type()}' to '{$newField->type()}'.",
                );
            }

            // Check if field was optional and is now required
            if (!$oldField->required() && $newField->required()) {
                $changes[] = new ApiChange(
                    ApiChange::CHANGED_REQUEST_FIELD_TYPE,
                    "{$endpointKey}/request/{$fieldName}",
                    "Request field '{$fieldName}' changed from optional to required.",
                    ApiChange::BREAKING,
                );
            }
        }
    }

    /**
     * @param array<int, ApiChange> $changes
     */
    private function compareResponseFields(
        string $endpointKey,
        ResourceDefinition $old,
        ResourceDefinition $new,
        array &$changes,
    ): void {
        /** @var array<string, ResponseField> $oldFields */
        $oldFields = [];
        foreach ($old->fields() as $field) {
            $oldFields[$field->name()] = $field;
        }

        /** @var array<string, ResponseField> $newFields */
        $newFields = [];
        foreach ($new->fields() as $field) {
            $newFields[$field->name()] = $field;
        }

        $oldNames = array_keys($oldFields);
        $newNames = array_keys($newFields);

        foreach (array_diff($oldNames, $newNames) as $removedField) {
            $changes[] = new ApiChange(
                ApiChange::REMOVED_RESPONSE_FIELD,
                "{$endpointKey}/response/{$removedField}",
                "Response field '{$removedField}' has been removed from '{$endpointKey}'.",
            );
        }

        foreach (array_diff($newNames, $oldNames) as $addedField) {
            $changes[] = new ApiChange(
                ApiChange::ADDED_RESPONSE_FIELD,
                "{$endpointKey}/response/{$addedField}",
                "Response field '{$addedField}' has been added to '{$endpointKey}'.",
            );
        }

        foreach (array_intersect($oldNames, $newNames) as $fieldName) {
            $oldField = $oldFields[$fieldName];
            $newField = $newFields[$fieldName];

            if ($oldField->type() !== $newField->type()) {
                $changes[] = new ApiChange(
                    ApiChange::CHANGED_RESPONSE_FIELD_TYPE,
                    "{$endpointKey}/response/{$fieldName}",
                    "Response field '{$fieldName}' type changed from '{$oldField->type()}' to '{$newField->type()}'.",
                );
            }

            // Check if field was non-nullable and is now nullable
            if (!$oldField->nullable() && $newField->nullable()) {
                $changes[] = new ApiChange(
                    ApiChange::CHANGED_RESPONSE_FIELD_TYPE,
                    "{$endpointKey}/response/{$fieldName}",
                    "Response field '{$fieldName}' changed from non-nullable to nullable.",
                    ApiChange::BREAKING,
                );
            }
        }
    }

    private function normalizeUri(string $uri): string
    {
        return preg_replace('/\{[^}]+\}/', '{}', $uri) ?: $uri;
    }
}
