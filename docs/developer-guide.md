# Developer Guide

Welcome to the `laravel-api-contract` Developer Guide! This manual covers everything you need to know to install, configure, and effectively use the package in your daily workflow.

---

## 1. Installation

Install the package via Composer. Because this package is primarily an analysis and generation tool, it is highly recommended to install it as a **dev dependency**:

```bash
composer require yab/laravel-api-contract --dev
```

If your Laravel version supports auto-discovery, the `LaravelApiContractServiceProvider` will be registered automatically.

---

## 2. Configuration

You can publish the default configuration file to customize the package's behavior.

```bash
php artisan vendor:publish --provider="Yab\LaravelApiContract\Providers\LaravelApiContractServiceProvider" --tag="config"
```

This will create a `config/api-contract.php` file in your application. Key configuration options include:
- **API Versioning:** Set the default version stamp for your contracts (e.g., `1.0.0`).
- **Route Filtering:** Define which route prefixes or middlewares the analyzer should scan (e.g., only routes beginning with `api/`).
- **Generator Output Paths:** Set default output directories for the generated artifacts.

---

## 3. Available Artisan Commands

The package provides a suite of Artisan commands prefixed with `api-contract:`.

- `api-contract:build`: Scans the app and builds the `api-contract.json` file.
- `api-contract:swagger`: Generates OpenAPI/Swagger documentation.
- `api-contract:typescript`: Generates TypeScript interfaces.
- `api-contract:client`: Generates a fully-typed frontend API client.
- `api-contract:postman`: Generates a Postman collection.
- `api-contract:docs`: Generates Markdown documentation.
- `api-contract:tests`: Generates PHPUnit feature tests.
- `api-contract:compare`: Compares two contracts to detect breaking changes.

*(There are also debugging commands like `api-contract:routes`, `api-contract:controllers`, etc., useful for inspecting the analysis engine's internal state).*

---

## 4. Generating the Contract

Before generating any artifacts, you must build the central API Contract. This command analyzes your existing Laravel routes, Form Requests, and Json Resources.

```bash
php artisan api-contract:build
```

**What it does:** 
It creates a unified JSON representation of your API and saves it, typically to `storage/api-contract.json`. All subsequent generator commands will read from this file.

---

## 5. Generating Artifacts

Once the contract is built, you can generate any artifact you need. 

*(Note: If you do not provide a `--output` or `--path` option, the generators will simply print the output to your terminal).*

### Generating Swagger (OpenAPI)
Generate an OpenAPI v3.0 JSON specification file that can be loaded directly into Swagger UI.

```bash
php artisan api-contract:swagger --path=public/swagger.json --pretty
```

### Generating TypeScript Interfaces
Keep your frontend type-safe by generating TypeScript interfaces that perfectly mirror your backend models and requests.

```bash
php artisan api-contract:typescript --output=resources/ts/types/api.ts
```

### Generating API Clients
Generate a fully functioning Axios/Fetch service layer for your frontend application. This client comes pre-configured with the exact routes, payloads, and expected response types.

```bash
php artisan api-contract:client --output=resources/ts/services/
```

### Generating PHPUnit Tests
Kickstart your TDD (Test-Driven Development) workflow by scaffolding feature tests for every analyzed endpoint.

```bash
php artisan api-contract:tests --output=tests/Feature/Api/
```

### Generating Markdown Documentation
If you host your documentation on GitHub, GitBook, or a static site generator, you can output clean, human-readable markdown.

```bash
php artisan api-contract:docs --path=docs/api.md
```

---

## 6. Comparing Versions (CI/CD)

The true power of an API Contract is preventing breaking changes. You can run the built-in comparator to diff two versions of your API.

1. **Before making changes**, save your current contract:
   ```bash
   cp storage/api-contract.json storage/old-contract.json
   ```
2. **Make your code changes** (e.g., remove a field from a Resource).
3. **Rebuild the contract:**
   ```bash
   php artisan api-contract:build
   ```
4. **Compare them:**
   ```bash
   php artisan api-contract:compare --old=storage/old-contract.json --new=storage/api-contract.json
   ```

If the package detects a breaking change (e.g., a required parameter was added, or an output field was removed), the command will exit with an error code and output a **Change Report**. This is incredibly useful in CI/CD pipelines to block PRs that break API compatibility.

---

## 7. Best Practices

To get the most out of `laravel-api-contract`, you should adhere strictly to standard Laravel conventions:

1. **Always use Form Requests:** Instead of `$request->validate()` inline within your controller, use dedicated `FormRequest` classes. The static analyzer reads the `rules()` method of the Form Request to deduce expected inputs.
2. **Always use JsonResources:** Do not return plain arrays or raw Eloquent models from your controllers. Always wrap responses in a `JsonResource` or `ResourceCollection`. The analyzer reads the `toArray()` method to deduce the output schema.
3. **Keep `toArray()` clean:** The AST parser is powerful, but it works best with explicit array returns. Avoid complex, dynamic, conditional logic deep inside `toArray()` if you want accurate TypeScript interfaces.
4. **Type-hint everything:** Ensure your controller methods are strictly type-hinted so the Reflection engine can accurately resolve dependencies.

---

## 8. Troubleshooting

### "My endpoint is missing from the contract!"
- Check your configuration. Ensure the route falls under the prefixes/middlewares defined in `config/api-contract.php`.
- Ensure your route is properly registered and accessible via `php artisan route:list`.

### "The TypeScript generator says my field is missing!"
- Verify that your `JsonResource` actually returns that field in its `toArray()` method. 
- Ensure you are running `php artisan api-contract:build` *after* you make changes to your PHP code, but *before* you run the typescript generator.

### "Path traversal detected" Error
- For security, the package strictly validates all `--output` and `--path` flags. You cannot write files outside of your project's base directory (`base_path()`) or the system temporary directory. Ensure your output paths resolve inside your Laravel project.
