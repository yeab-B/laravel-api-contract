<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Yab\LaravelApiContract\Tests\TestCase;

class CompareCommandTest extends TestCase
{
    private string $oldPath;
    private string $newPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oldPath = tempnam(sys_get_temp_dir(), 'api-contract-old-');
        $this->newPath = tempnam(sys_get_temp_dir(), 'api-contract-new-');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->oldPath)) {
            unlink($this->oldPath);
        }
        if (file_exists($this->newPath)) {
            unlink($this->newPath);
        }

        parent::tearDown();
    }

    public function test_fails_when_old_option_missing(): void
    {
        $this->artisan('api-contract:compare', ['--new' => $this->newPath])
            ->assertFailed();
    }

    public function test_fails_when_new_option_missing(): void
    {
        $this->artisan('api-contract:compare', ['--old' => $this->oldPath])
            ->assertFailed();
    }

    public function test_fails_when_old_file_not_found(): void
    {
        $this->artisan('api-contract:compare', [
            '--old' => '/nonexistent/file.json',
            '--new' => $this->newPath,
        ])->assertFailed();
    }

    public function test_fails_when_new_file_not_found(): void
    {
        $this->artisan('api-contract:compare', [
            '--old' => $this->oldPath,
            '--new' => '/nonexistent/file.json',
        ])->assertFailed();
    }

    public function test_succeeds_with_no_changes(): void
    {
        $contract = [
            'name' => 'API',
            'version' => '1.0',
            'authentication' => 'sanctum',
            'endpoints' => [],
            'metadata' => [],
        ];

        file_put_contents($this->oldPath, json_encode($contract));
        file_put_contents($this->newPath, json_encode($contract));

        $this->artisan('api-contract:compare', [
            '--old' => $this->oldPath,
            '--new' => $this->newPath,
        ])->assertSuccessful();
    }

    public function test_fails_with_breaking_changes(): void
    {
        $oldContract = [
            'name' => 'API',
            'version' => '1.0',
            'authentication' => 'sanctum',
            'endpoints' => [
                [
                    'method' => 'GET',
                    'uri' => 'api/users',
                    'name' => null,
                    'controller' => null,
                    'middleware' => [],
                    'parameters' => [],
                    'request' => null,
                    'response' => null,
                ],
            ],
            'metadata' => [],
        ];

        $newContract = [
            'name' => 'API',
            'version' => '2.0',
            'authentication' => 'sanctum',
            'endpoints' => [],
            'metadata' => [],
        ];

        file_put_contents($this->oldPath, json_encode($oldContract));
        file_put_contents($this->newPath, json_encode($newContract));

        $this->artisan('api-contract:compare', [
            '--old' => $this->oldPath,
            '--new' => $this->newPath,
        ])->assertFailed();
    }

    public function test_outputs_json_by_default(): void
    {
        $contract = [
            'name' => 'API',
            'version' => '1.0',
            'authentication' => 'sanctum',
            'endpoints' => [],
            'metadata' => [],
        ];

        file_put_contents($this->oldPath, json_encode($contract));
        file_put_contents($this->newPath, json_encode($contract));

        $this->artisan('api-contract:compare', [
            '--old' => $this->oldPath,
            '--new' => $this->newPath,
        ])->assertSuccessful();
    }

    public function test_writes_report_to_file(): void
    {
        $contract = [
            'name' => 'API',
            'version' => '1.0',
            'authentication' => 'sanctum',
            'endpoints' => [],
            'metadata' => [],
        ];

        file_put_contents($this->oldPath, json_encode($contract));
        file_put_contents($this->newPath, json_encode($contract));

        $outputPath = tempnam(sys_get_temp_dir(), 'api-contract-report-');

        try {
            $this->artisan('api-contract:compare', [
                '--old' => $this->oldPath,
                '--new' => $this->newPath,
                '--output' => $outputPath,
            ])->assertSuccessful();

            $this->assertFileExists($outputPath);
            $this->assertJson(file_get_contents($outputPath));
        } finally {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    public function test_outputs_markdown_with_format_option(): void
    {
        $contract = [
            'name' => 'API',
            'version' => '1.0',
            'authentication' => 'sanctum',
            'endpoints' => [],
            'metadata' => [],
        ];

        file_put_contents($this->oldPath, json_encode($contract));
        file_put_contents($this->newPath, json_encode($contract));

        $this->artisan('api-contract:compare', [
            '--old' => $this->oldPath,
            '--new' => $this->newPath,
            '--format' => 'markdown',
        ])->assertSuccessful();
    }
}
