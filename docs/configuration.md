# Configuration Guide

The `laravel-api-contract` package is designed to work seamlessly out-of-the-box for standard Laravel installations. However, as your application grows, you may need to customize how the analyzer discovers routes or where artifacts are generated.

This document covers all available configuration options, their defaults, when you should change them, and best practices for different project scales.

---

## Publishing the Configuration

To override the default settings, you must first publish the configuration file to your application's `config` directory:

```bash
php artisan vendor:publish --provider="Yab\LaravelApiContract\Providers\LaravelApiContractServiceProvider" --tag="config"
```

This will create a `config/api-contract.php` file in your project.

---

## Configuration Options

### 1. `contract.version`

The version stamp applied to your generated `ApiContract`.

- **Default Value:** `'1.0.0'`
- **What it does:** This string is injected into the root of the generated `api-contract.json`. The `SwaggerGenerator` also uses this to set the `info.version` field in your OpenAPI documentation.
- **When to change it:** 
  - Change this whenever you are releasing a major, intentionally breaking update to your API (e.g., `'2.0.0'`).
  - Many enterprise teams automate this field in their CI/CD pipeline by injecting the current Git tag or commit hash.

### 2. `routes.prefixes`

An array of URI prefixes that the `RouteAnalyzer` should scan.

- **Default Value:** `['api']`
- **What it does:** Instructs the analyzer to ignore web routes, admin panels, or internal system endpoints. Only routes beginning with one of these prefixes will be included in the API Contract.
- **When to change it:**
  - If your API does not use the `/api` prefix (e.g., you route via subdomains like `api.example.com/v1`).
  - If you have multiple distinct APIs (e.g., `['api/v1', 'api/v2']`) and you want them all in a single contract.

### 3. `routes.middlewares`

An array of route middlewares to use as a secondary filter mechanism.

- **Default Value:** `[]` (empty)
- **What it does:** If populated, the analyzer will *only* process routes that have at least one of these middlewares attached (e.g., `['api']`). 
- **When to change it:** 
  - Change this if you prefer to filter API routes by the middleware group assigned in `RouteServiceProvider` rather than by URI prefixes.

### 4. `generators.paths`

Default output paths for the various artifact generators.

- **Default Values:**
  - `'contract' => storage_path('api-contract.json')`
  - `'swagger' => public_path('swagger.json')`
  - `'typescript' => resource_path('ts/types/api.ts')`
  - `'client' => resource_path('ts/services/api-client.ts')`
  - `'postman' => storage_path('postman-collection.json')`
  - `'docs' => base_path('docs/api.md')`
  - `'tests' => base_path('tests/Feature/Api/')`
- **What it does:** When you run a generator command (like `php artisan api-contract:typescript`) without passing an explicit `--output` flag, it falls back to these paths.
- **When to change it:**
  - Change these defaults to match your specific frontend framework or monorepo structure, ensuring you never have to type long `--output` flags in your terminal.

---

## Recommended Configurations by Project Scale

Depending on the size of your team and application, you should structure your API Contract workflow differently.

### 1. Small Projects / Solo Developers

If you are a solo developer building an SPA (Single Page Application) with Laravel, Inertia/Vue, or React, keep it simple.

**Recommendations:**
- Leave `routes.prefixes` as `['api']`.
- Set your `generators.paths.typescript` directly into your frontend components folder (e.g., `resources/js/types.ts`).
- **Workflow:** You don't need Postman or Markdown docs. Run `api-contract:build` and `api-contract:typescript` during local development to keep your frontend strictly typed.

```php
// config/api-contract.php
return [
    'routes' => [
        'prefixes' => ['api'],
    ],
    'generators' => [
        'paths' => [
            'typescript' => resource_path('js/types/api.ts'),
            'client' => resource_path('js/services/ApiClient.ts'),
        ],
    ],
];
```

### 2. Medium Projects / Cross-Functional Teams

If you have separate backend and frontend developers (or a dedicated mobile app team), the contract becomes your communication layer.

**Recommendations:**
- Use semantic versioning in `contract.version` (e.g., `'1.2.4'`).
- Utilize `api-contract:swagger` to provide a searchable, visual UI for the frontend team to explore.
- Point the Swagger output directly to the `public/` directory so the Swagger UI can fetch it natively.

```php
// config/api-contract.php
return [
    'contract' => [
        'version' => env('API_VERSION', '1.0.0'),
    ],
    'routes' => [
        'prefixes' => ['api/v1'],
    ],
    'generators' => [
        'paths' => [
            'swagger' => public_path('docs/swagger.json'),
            'postman' => storage_path('app/public/postman.json'),
        ],
    ],
];
```

### 3. Enterprise / Monorepo Architecture

Enterprise environments usually have multiple APIs (Mobile API, Web API, Third-Party B2B API), strict CI/CD pipelines, and severe consequences for breaking changes.

**Recommendations:**
- Do not commit the generated `api-contract.json` manually. Instead, generate it dynamically during the CI pipeline.
- Separate your APIs using different configuration files or env variables.
- Strictly rely on `api-contract:compare` in GitHub Actions/GitLab CI to fail builds if a breaking change is detected.
- Change the output paths to point towards shared monorepo packages (e.g., an `@acme/api-types` internal NPM package).

```php
// config/api-contract.php
return [
    'contract' => [
        // Injected by CI/CD
        'version' => env('GITHUB_SHA', 'latest'),
    ],
    'routes' => [
        // Strict mapping for the B2B Gateway
        'prefixes' => ['gateway/v2'],
        'middlewares' => ['auth:b2b-token', 'throttle:api'],
    ],
    'generators' => [
        'paths' => [
            // Write directly to the shared internal NPM monorepo package
            'typescript' => base_path('../packages/frontend-types/src/index.ts'),
            'client' => base_path('../packages/api-client/src/index.ts'),
        ],
    ],
];
```
