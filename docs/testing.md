# Testing Strategy & Generated Tests

In traditional development workflows, maintaining comprehensive test coverage for APIs is notoriously tedious. Developers must manually scaffold test classes, hand-write dummy payloads, and meticulously assert every returned JSON field.

`laravel-api-contract` drastically simplifies this by scaffolding your tests for you. This document outlines the package's testing philosophy and how you can leverage it to achieve rock-solid API stability.

---

## The Philosophy of Generated Tests

The package provides the `api-contract:tests` command, which consumes the central API Contract and outputs boilerplate PHPUnit feature tests. 

**Important:** The package does *not* write your business logic assertions for you. It writes the *structural* assertions. 

The philosophy is simple: **Eliminate the boilerplate so developers can focus on business logic.**

When the generator runs, it:
1. Creates a test file for every controller (e.g., `PostControllerTest.php`).
2. Creates a test method for every endpoint (e.g., `public function test_it_can_store_a_post()`).
3. Uses the `RequestDefinition` to generate a valid dummy JSON payload matching your Form Request.
4. Uses the `EndpointDefinition` to dispatch the correct HTTP method (e.g., `$this->postJson(...)`).
5. Uses the `ResourceDefinition` to assert the returned JSON structure is exactly correct.

---

## Scaffolding Tests

To generate the test suite, run:

```bash
php artisan api-contract:tests --output=tests/Feature/Api/
```

### Example Scenario

If you have a `store` method on `UserController` that requires a `name` and `email` and returns a `UserResource`, the generator will output a test like this:

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_store_a_user()
    {
        // 1. Scaffolded payload based on Form Request
        $payload = [
            'name' => 'Test String',
            'email' => 'test@example.com',
        ];

        // 2. Appropriate HTTP call
        $response = $this->postJson('/api/users', $payload);

        // 3. Basic status assertion
        $response->assertStatus(201);

        // 4. Structural assertion based on JsonResource
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'created_at'
            ]
        ]);
        
        // TODO: Add your business logic assertions here (e.g., database checks)
    }
}
```

## Developer Workflow

1. **Write the Code:** Write your Controller, Form Request, and JsonResource.
2. **Build the Contract:** Run `php artisan api-contract:build`.
3. **Generate the Tests:** Run `php artisan api-contract:tests`.
4. **Flesh out the Logic:** Open the generated test file and replace the generic payload values (`'Test String'`) with your factories. Add any specific database assertions (`$this->assertDatabaseHas(...)`).

By letting the package write the structural assertions, you ensure that if a developer ever removes a field from a `JsonResource` in the future, the test suite will immediately fail, catching the regression instantly.
