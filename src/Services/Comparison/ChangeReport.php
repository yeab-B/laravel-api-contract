<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Comparison;

class ChangeReport
{
    /** @param array<int, ApiChange> $changes */
    public function __construct(
        private readonly string $oldVersion,
        private readonly string $newVersion,
        private readonly array $changes,
    ) {
    }

    public function oldVersion(): string
    {
        return $this->oldVersion;
    }

    public function newVersion(): string
    {
        return $this->newVersion;
    }

    /** @return array<int, ApiChange> */
    public function changes(): array
    {
        return $this->changes;
    }

    /** @return array<int, ApiChange> */
    public function breakingChanges(): array
    {
        return array_values(
            array_filter($this->changes, static fn (ApiChange $change) => $change->isBreaking()),
        );
    }

    /** @return array<int, ApiChange> */
    public function nonBreakingChanges(): array
    {
        return array_values(
            array_filter($this->changes, static fn (ApiChange $change) => !$change->isBreaking()),
        );
    }

    public function hasBreakingChanges(): bool
    {
        return $this->breakingChanges() !== [];
    }

    /** @return array<string, int> */
    public function summary(): array
    {
        $total = count($this->changes);
        $breaking = count($this->breakingChanges());

        return [
            'total' => $total,
            'breaking' => $breaking,
            'non_breaking' => $total - $breaking,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'old_version' => $this->oldVersion,
            'new_version' => $this->newVersion,
            'summary' => $this->summary(),
            'has_breaking_changes' => $this->hasBreakingChanges(),
            'changes' => array_map(
                static fn (ApiChange $change) => $change->toArray(),
                $this->changes,
            ),
        ];
    }

    public function toJson(bool $pretty = true): string
    {
        $flags = $pretty ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 0;

        return json_encode($this->toArray(), $flags) ?: '{}';
    }

    public function toMarkdown(): string
    {
        $lines = [];
        $lines[] = "# API Contract Comparison: {$this->oldVersion} → {$this->newVersion}";
        $lines[] = '';
        $summary = $this->summary();
        $lines[] = "**Summary:** {$summary['total']} change(s) " .
                   "— {$summary['breaking']} breaking, " .
                   "{$summary['non_breaking']} non-breaking.";
        $lines[] = '';

        if ($this->hasBreakingChanges()) {
            $lines[] = '## Breaking Changes';
            $lines[] = '';
            foreach ($this->breakingChanges() as $change) {
                $lines[] = "- **{$change->location()}**: {$change->description()}";
            }
            $lines[] = '';
        }

        $nonBreaking = $this->nonBreakingChanges();
        if ($nonBreaking !== []) {
            $lines[] = '## Non-Breaking Changes';
            $lines[] = '';
            foreach ($nonBreaking as $change) {
                $lines[] = "- **{$change->location()}**: {$change->description()}";
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
