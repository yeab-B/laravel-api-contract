<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Support;

class ValidationRuleParser
{
    /**
     * Parse a single validation rule string into its structured components.
     *
     * @param array<int, string>|string $rules
     *
     * @return array{type: string, required: bool, rules: array<int, string>}
     */
    public function parse(string $fieldName, array|string $rules): array
    {
        $ruleStrings = $this->normalizeRules($rules);
        $required = $this->detectRequired($ruleStrings);
        $type = $this->detectType($ruleStrings);

        return [
            'type' => $type,
            'required' => $required,
            'rules' => $ruleStrings,
        ];
    }

    /**
     * @param array<int, string> $ruleStrings
     */
    public function detectType(array $ruleStrings): string
    {
        $priority = [
            'array', 'file', 'image', 'boolean', 'numeric',
            'integer', 'email', 'url', 'ip', 'date', 'json', 'string',
        ];

        foreach ($priority as $type) {
            if ($this->hasRule($ruleStrings, $type)) {
                return $type;
            }
        }

        return 'mixed';
    }

    /**
     * @param array<int, string> $ruleStrings
     */
    public function detectRequired(array $ruleStrings): bool
    {
        if ($this->hasRule($ruleStrings, 'nullable')) {
            return false;
        }

        return $this->hasRule($ruleStrings, 'required');
    }

    /**
     * @param array<int, string|object>|string $rules
     *
     * @return array<int, string>
     */
    public function normalizeRules(array|string $rules): array
    {
        if (is_string($rules)) {
            return explode('|', $rules);
        }

        $normalized = [];

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $normalized[] = $rule;

                continue;
            }

            if (is_object($rule) && method_exists($rule, '__toString')) {
                $normalized[] = (string) $rule;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $rules
     */
    private function hasRule(array $rules, string $name): bool
    {
        foreach ($rules as $rule) {
            $ruleName = explode(':', $rule, 2)[0];

            if ($ruleName === $name) {
                return true;
            }
        }

        return false;
    }
}
