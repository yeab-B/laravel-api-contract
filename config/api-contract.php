<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Output Generators
    |--------------------------------------------------------------------------
    |
    | Register the generators that produce output from your API contracts.
    | Each generator maps a name to a class that implements the
    | GeneratorInterface.
    |
    | Supported generators out of the box:
    | - 'swagger'     → OpenAPI / Swagger specification
    | - 'typescript'  → TypeScript type definitions and API client
    | - 'postman'     → Postman collection (v2.1)
    | - 'markdown'    → Markdown API documentation
    | - 'tests'       → PHPUnit feature tests
    |
    */

    'generators' => [
        'swagger' => \Yab\LaravelApiContract\Generators\Swagger\SwaggerGenerator::class,
        'typescript' => \Yab\LaravelApiContract\Generators\TypeScript\TypeScriptGenerator::class,
        'client' => \Yab\LaravelApiContract\Generators\Client\ClientGenerator::class,
        'postman' => \Yab\LaravelApiContract\Generators\Postman\PostmanGenerator::class,
        'markdown' => \Yab\LaravelApiContract\Generators\Markdown\MarkdownGenerator::class,
        'tests' => \Yab\LaravelApiContract\Generators\Test\TestGenerator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Framework
    |--------------------------------------------------------------------------
    |
    | When generating TypeScript clients, this setting controls the
    | framework-specific patterns (e.g. React hooks, Vue composables).
    |
    | Supported: 'react', 'vue', 'angular', or null for vanilla TypeScript.
    |
    */

    'frontend_framework' => env('API_CONTRACT_FRONTEND', 'react'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Driver
    |--------------------------------------------------------------------------
    |
    | The authentication mechanism used by your API. This influences how
    | generated clients handle authorization headers.
    |
    | Supported: 'sanctum', 'passport', 'jwt', 'session', 'none'
    |
    */

    'auth_driver' => env('API_CONTRACT_AUTH', 'sanctum'),

    /*
    |--------------------------------------------------------------------------
    | API Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix used to identify API routes. Routes whose URI starts
    | with this prefix (or are in the 'api' middleware group) will be
    | included in discovery.
    |
    */

    'api_prefix' => env('API_CONTRACT_PREFIX', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Define where the package reads contracts from and where it writes
    | generated output.
    |
    */

    'paths' => [

        'output' => resource_path('api-docs'),

        'scan' => [
            app_path('Http/Controllers'),
            app_path('Models'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Generation Options
    |--------------------------------------------------------------------------
    |
    | Fine-tune the generated output.
    |
    */

    'generation' => [

        'include_paths' => ['api/*'],

        'exclude_paths' => [],

        'pretty_print' => env('API_CONTRACT_PRETTY', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Contract Metadata
    |--------------------------------------------------------------------------
    |
    | These values are embedded into the generated ApiContract object and
    | exposed to every downstream generator.
    |
    */

    'swagger' => [
        'output_path' => env('API_CONTRACT_SWAGGER_PATH', resource_path('api-docs/swagger.json')),
        'pretty_print' => env('API_CONTRACT_SWAGGER_PRETTY', true),
    ],

    'typescript' => [
        'enabled' => env('API_CONTRACT_TYPESCRIPT_ENABLED', true),
        'output' => env('API_CONTRACT_TYPESCRIPT_OUTPUT', resource_path('js/types/api.ts')),
    ],

    'client' => [
        'enabled' => env('API_CONTRACT_CLIENT_ENABLED', true),
        'framework' => env('API_CONTRACT_CLIENT_FRAMEWORK', 'axios'),
        'output' => env('API_CONTRACT_CLIENT_OUTPUT', resource_path('js/api')),
    ],

    'postman' => [
        'enabled' => env('API_CONTRACT_POSTMAN_ENABLED', true),
        'output' => env('API_CONTRACT_POSTMAN_OUTPUT', storage_path('api-contract/postman.json')),
    ],

    'markdown' => [
        'enabled' => env('API_CONTRACT_MARKDOWN_ENABLED', true),
        'output' => env('API_CONTRACT_MARKDOWN_OUTPUT', base_path('docs/API.md')),
    ],

    'tests' => [
        'enabled' => env('API_CONTRACT_TESTS_ENABLED', true),
        'output' => env('API_CONTRACT_TESTS_OUTPUT', base_path('tests/Feature/API')),
    ],

    'contract' => [
        'name' => env('API_CONTRACT_NAME', 'Laravel API'),
        'version' => env('API_CONTRACT_VERSION', '1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Version Comparison
    |--------------------------------------------------------------------------
    |
    | Settings for comparing contract snapshots and detecting breaking
    | changes between API versions.
    |
    */

    'comparison' => [
        'default_format' => env('API_CONTRACT_COMPARISON_FORMAT', 'json'),
        'output_path' => env('API_CONTRACT_COMPARISON_OUTPUT', storage_path('api-contract/comparison-report.json')),
    ],

];
