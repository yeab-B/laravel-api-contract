<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Yab\LaravelApiContract\Tests\TestCase;

class RoutesCommandEmptyTest extends TestCase
{
    public function test_command_shows_warning_when_no_api_routes(): void
    {
        $this->artisan('api-contract:routes')
            ->expectsOutputToContain('No API routes discovered.')
            ->assertSuccessful();
    }
}
