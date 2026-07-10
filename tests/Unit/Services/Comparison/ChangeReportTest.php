<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Comparison;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Comparison\ApiChange;
use Yab\LaravelApiContract\Services\Comparison\ChangeReport;

class ChangeReportTest extends TestCase
{
    public function test_constructs_with_versions_and_changes(): void
    {
        $changes = [];
        $report = new ChangeReport('1.0', '2.0', $changes);

        $this->assertSame('1.0', $report->oldVersion());
        $this->assertSame('2.0', $report->newVersion());
        $this->assertSame([], $report->changes());
    }

    public function test_summary_returns_correct_counts(): void
    {
        $changes = [
            new ApiChange(ApiChange::ADDED_ENDPOINT, 'GET api/posts', 'Added.'),
            new ApiChange(ApiChange::REMOVED_ENDPOINT, 'GET api/users', 'Removed.'),
            new ApiChange(ApiChange::ADDED_RESPONSE_FIELD, 'GET api/posts/response/name', 'Added.'),
        ];

        $report = new ChangeReport('1.0', '2.0', $changes);

        $this->assertSame(3, $report->summary()['total']);
        $this->assertSame(1, $report->summary()['breaking']);
        $this->assertSame(2, $report->summary()['non_breaking']);
        $this->assertTrue($report->hasBreakingChanges());
    }

    public function test_has_breaking_returns_false_when_none(): void
    {
        $changes = [
            new ApiChange(ApiChange::ADDED_ENDPOINT, 'GET api/posts', 'Added.'),
        ];

        $report = new ChangeReport('1.0', '2.0', $changes);

        $this->assertFalse($report->hasBreakingChanges());
    }

    public function test_breaking_and_non_breaking_filters(): void
    {
        $breaking = new ApiChange(ApiChange::REMOVED_ENDPOINT, 'GET api/users', 'Removed.');
        $nonBreaking = new ApiChange(ApiChange::ADDED_ENDPOINT, 'GET api/posts', 'Added.');

        $report = new ChangeReport('1.0', '2.0', [$breaking, $nonBreaking]);

        $this->assertCount(1, $report->breakingChanges());
        $this->assertSame($breaking, $report->breakingChanges()[0]);
        $this->assertCount(1, $report->nonBreakingChanges());
        $this->assertSame($nonBreaking, $report->nonBreakingChanges()[0]);
    }

    public function test_to_array(): void
    {
        $changes = [
            new ApiChange(ApiChange::ADDED_ENDPOINT, 'GET api/posts', 'Added.'),
        ];

        $report = new ChangeReport('1.0', '2.0', $changes);
        $array = $report->toArray();

        $this->assertSame('1.0', $array['old_version']);
        $this->assertSame('2.0', $array['new_version']);
        $this->assertFalse($array['has_breaking_changes']);
        $this->assertCount(1, $array['changes']);
    }

    public function test_to_json(): void
    {
        $report = new ChangeReport('1.0', '2.0', []);
        $json = $report->toJson();

        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertSame('1.0', $data['old_version']);
    }

    public function test_to_markdown(): void
    {
        $changes = [
            new ApiChange(ApiChange::REMOVED_ENDPOINT, 'GET api/users', "Endpoint 'GET api/users' has been removed."),
            new ApiChange(ApiChange::ADDED_ENDPOINT, 'GET api/posts', "Endpoint 'GET api/posts' has been added."),
        ];

        $report = new ChangeReport('1.0', '2.0', $changes);
        $markdown = $report->toMarkdown();

        $this->assertStringContainsString('1.0 → 2.0', $markdown);
        $this->assertStringContainsString('Breaking Changes', $markdown);
        $this->assertStringContainsString('Non-Breaking Changes', $markdown);
        $this->assertStringContainsString('GET api/users', $markdown);
        $this->assertStringContainsString('GET api/posts', $markdown);
    }

    public function test_to_markdown_without_breaking(): void
    {
        $changes = [
            new ApiChange(ApiChange::ADDED_ENDPOINT, 'GET api/posts', "Endpoint 'GET api/posts' has been added."),
        ];

        $report = new ChangeReport('1.0', '2.0', $changes);
        $markdown = $report->toMarkdown();

        $this->assertStringNotContainsString('## Breaking Changes', $markdown);
        $this->assertStringContainsString('## Non-Breaking Changes', $markdown);
    }

    public function test_to_markdown_without_non_breaking(): void
    {
        $changes = [
            new ApiChange(ApiChange::REMOVED_ENDPOINT, 'GET api/users', "Endpoint 'GET api/users' has been removed."),
        ];

        $report = new ChangeReport('1.0', '2.0', $changes);
        $markdown = $report->toMarkdown();

        $this->assertStringContainsString('## Breaking Changes', $markdown);
        $this->assertStringNotContainsString('## Non-Breaking Changes', $markdown);
    }
}
