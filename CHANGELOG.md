# Changelog

All notable changes to `laravel-api-contract` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-10

### Added

#### Core
- **API Contract Builder** (`api-contract:build`): Automatically analyzes Laravel routes, controllers, Form Requests, and API Resources to generate a unified `api-contract.json` — the single source of truth for your entire API.
- **Route Discovery Engine**: Registers and inspects all `api` middleware group routes.
- **Controller Analyzer**: Uses PHP Reflection to introspect controller method signatures.
- **Form Request Analyzer**: Parses `rules()` methods to build structured validation field definitions.
- **API Resource Analyzer**: Parses `toArray()` methods to extract response field shapes and relationships.

#### Generators
- **Swagger/OpenAPI Generator** (`api-contract:swagger`): Produces OpenAPI 3.0 JSON specifications, ready for Swagger UI.
- **TypeScript Generator** (`api-contract:typescript`): Generates fully typed TypeScript interfaces from Form Requests and API Resources.
- **API Client Generator** (`api-contract:client`): Generates a typed Axios service layer for frontend applications.
- **Postman Collection Generator** (`api-contract:postman`): Produces a Postman v2.1 Collection JSON.
- **Markdown Documentation Generator** (`api-contract:docs`): Generates human-readable Markdown API documentation.
- **PHPUnit Test Generator** (`api-contract:tests`): Scaffolds feature test stubs for every discovered endpoint.
- **Contract Comparator** (`api-contract:compare`): Diffs two contract files and emits breaking vs. non-breaking change reports.

#### Architecture
- `ApiContractContract` interface as the primary abstraction.
- Typed DTOs: `EndpointDefinition`, `RequestDefinition`, `ResourceDefinition`, `ValidationField`, `ResponseField`.
- `ContractSerializer`: Serializes/deserializes contracts to/from JSON with path-safety checks.
- `Configuration` class wrapping the config repository with safe defaults.
- Full `LaravelApiContractServiceProvider` with config publishing and Artisan command registration.
- `ApiContract` Facade.

#### Quality
- 441 tests across Unit and Feature suites.
- PHPStan at level `max` with 0 errors.
- PSR-12 compliant across all source files.
- `declare(strict_types=1)` in every source file.
- Security: Path traversal prevention via `Configuration::ensureSafePath()`.

#### Developer Experience
- Full `docs/` directory with 15 documentation pages covering architecture, commands, configuration, security, extending, and FAQ.
- GitHub Actions CI workflow for automated testing across PHP 8.2/8.3 and Laravel 11.

[1.0.0]: https://github.com/yeab-B/laravel-api-contract/releases/tag/v1.0.0
