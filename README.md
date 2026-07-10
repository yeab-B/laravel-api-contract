<p align="center">
    <h1 align="center">Laravel API Contract</h1>
    <p align="center">The ultimate "Write once. Generate everything." toolkit for Laravel APIs.</p>
</p>

<p align="center">
    <a href="https://packagist.org/packages/yeab/laravel-api-contract">
        <img src="https://img.shields.io/packagist/v/yeab/laravel-api-contract.svg?style=flat-square" alt="Latest Version on Packagist">
    </a>
    <a href="https://packagist.org/packages/yeab/laravel-api-contract">
        <img src="https://img.shields.io/packagist/dt/yeab/laravel-api-contract.svg?style=flat-square" alt="Total Downloads">
    </a>
    <a href="https://github.com/yeab-B/laravel-api-contract/actions">
        <img src="https://img.shields.io/github/actions/workflow/status/yeab-B/laravel-api-contract/tests.yml?branch=main&style=flat-square" alt="Build Status">
    </a>
    <a href="https://packagist.org/packages/yeab/laravel-api-contract">
        <img src="https://img.shields.io/packagist/l/yeab/laravel-api-contract.svg?style=flat-square" alt="License">
    </a>
</p>

---

## 📖 Introduction

**Laravel API Contract** is an advanced, fully automated architecture utility for Laravel applications. It statically analyzes your existing Laravel routes, controllers, Form Requests, and API Resources to automatically generate a comprehensive **API Contract**. 

From this single source of truth, it instantly generates Swagger documentation, TypeScript interfaces, typed API clients, Postman collections, Markdown docs, PHPUnit feature tests, and backwards-compatibility reports.

## 🎯 Core Philosophy

**"Write once. Generate everything."**

You shouldn't have to write your validation rules in a Form Request, duplicate them in OpenAPI annotations, and then write them *again* as TypeScript interfaces for your frontend. Laravel API Contract eliminates this friction. Write clean, standard Laravel code, and let the package do the rest.

## 💡 Why This Package Exists

Building APIs usually means maintaining parallel systems. When you update an endpoint in your controller, you also have to update your Postman collection, notify the frontend team to update their types, fix the Swagger annotations, and write the tests. 

This causes friction, drift, and broken integrations. We built this package to ensure that your Laravel code is the *only* source of truth.

## 🛠️ Problems It Solves

- **Documentation Drift:** Your OpenAPI/Swagger docs are never out of sync with your code because they are generated *from* your code.
- **Frontend/Backend Friction:** Frontend developers get fully typed API clients and TypeScript interfaces automatically.
- **Breaking Changes:** The built-in contract comparator warns you if you accidentally introduce a breaking change to your API.
- **Testing Fatigue:** Automatically scaffolds PHPUnit feature tests for every detected endpoint.

## ✨ Features

- 🧠 **Zero Annotations Required:** Uses static analysis via Reflection and AST parsing. No ugly docblocks or attributes cluttering your controllers.
- ⚡ **Blazing Fast:** Intelligent file caching and optimal Reflection usage ensures it analyzes large APIs in milliseconds.
- 🔒 **Secure by Default:** Hardened against path traversal with strict directory write rules.
- 🔄 **Intelligent Diffing:** Compares versions of your API and outputs detailed breaking-change reports.
- 🚀 **Framework Native:** Integrates seamlessly into the Laravel ecosystem. Feels like a core component.

## 🏗️ High-Level Architecture Overview

1. **Route Discovery Engine:** Finds all API routes registered in your application.
2. **Controller Analysis Engine:** Inspects your controller methods to determine logic and parameters.
3. **Form Request & Validation Analyzer:** Parses `rules()` to determine expected input payloads and validation states.
4. **API Resource Analyzer:** Parses your `toArray()` methods to determine the exact shape of your JSON responses.
5. **Contract Builder:** Merges all analyzed metadata into a single, unified `ApiContract` JSON representation.
6. **Generators:** Consumes the `ApiContract` to emit various artifacts.

## 📦 Generated Outputs

Once your application is analyzed, you can generate:

- **Swagger/OpenAPI (v3.0):** Ready to be served by Swagger UI.
- **TypeScript Interfaces:** 100% accurate types matching your Form Requests and API Resources.
- **Typed API Client (TypeScript):** A ready-to-use Axios/Fetch service layer for your frontend.
- **Postman Collection (v2.1):** Instantly shareable with your team.
- **Markdown Documentation:** Beautiful, human-readable markdown for static site generators.
- **PHPUnit API Tests:** Automatically scaffolded feature tests for every route.
- **Change Reports:** Breaking vs. Non-breaking API change detection over time.

## 📥 Installation

Require the package via Composer. It is recommended to install it as a dev dependency unless you are generating contracts in production:

```bash
composer require yab/laravel-api-contract --dev
```

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --provider="Yab\LaravelApiContract\Providers\LaravelApiContractServiceProvider" --tag="config"
```

## 🚀 Quick Start

To instantly analyze your API and build the core contract file (usually written to `storage/api-contract.json`):

```bash
php artisan api-contract:build
```

Now, you can generate everything else from it!

```bash
# Generate Swagger Docs
php artisan api-contract:swagger --path=public/swagger.json

# Generate TypeScript Interfaces
php artisan api-contract:typescript --output=resources/ts/types/api.ts

# Generate an API Client
php artisan api-contract:client --output=resources/ts/services/

# Scaffold PHPUnit Tests
php artisan api-contract:tests --output=tests/Feature/Api/
```

## 💻 Basic Usage

The package works automatically by inspecting standard Laravel conventions.

```php
// routes/api.php
Route::post('/users', [UserController::class, 'store'])->name('users.store');

// app/Http/Controllers/UserController.php
public function store(StoreUserRequest $request)
{
    $user = User::create($request->validated());
    return new UserResource($user);
}

// app/Http/Requests/StoreUserRequest.php
public function rules()
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users'],
    ];
}

// app/Http/Resources/UserResource.php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
    ];
}
```

Run `php artisan api-contract:build`, and the package will figure out exactly what `/users` accepts and what it returns!

## 🛠️ Available Artisan Commands

| Command | Description |
|---|---|
| `api-contract:build` | Analyze the application and build the central `api-contract.json` file. |
| `api-contract:swagger` | Generate an OpenAPI/Swagger v3.0 JSON specification. |
| `api-contract:typescript` | Generate TypeScript interface definition files. |
| `api-contract:client` | Generate a fully-typed TypeScript API client service. |
| `api-contract:postman` | Generate a Postman v2.1 Collection JSON file. |
| `api-contract:docs` | Generate human-readable Markdown documentation. |
| `api-contract:tests` | Generate boilerplate PHPUnit feature tests. |
| `api-contract:compare` | Compare two contract files and output a breaking-change report. |

## 📚 Documentation Index

For deep dives into configuration and advanced generator options, please refer to the documentation:

- [Introduction](docs/introduction.md)
- [Configuration Guide](docs/configuration.md)
- [Architecture Overview](docs/architecture.md)
- [Available Commands](docs/commands.md)
- [API Contract Definition](docs/api-contract.md)
- [Comparison & Versioning](docs/comparison-and-versioning.md)
- [Security & Performance](docs/security-and-performance.md)
- [Extending the Package](docs/extending.md)
- [Developer Guide](docs/developer-guide.md)
- [FAQ](docs/faq.md)
- [Glossary](docs/glossary.md)

## 🗺️ Roadmap

- [ ] Support for Data Transfer Objects (DTOs) parsing (Spatie Data).
- [ ] Enum parsing for strict typing in Swagger and TypeScript.
- [ ] Support for generating Nuxt 3/Vue 3 composables.

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Security Vulnerabilities

If you discover any security-related issues, please email eliora.main@gmail.com instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
