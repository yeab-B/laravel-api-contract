# Extending Laravel API Contract

The `laravel-api-contract` package was built from the ground up with extensibility in mind. Because it adheres strictly to SOLID principles, nearly every layer of the package is abstracted behind an interface and resolved out of the Laravel Service Container. 

This document explains the extension architecture and provides examples for contributors and advanced users who want to add new capabilities to the package.

---

## 1. The Extension Architecture

The package relies heavily on **Interface Binding**. The core engine (`ContractBuilder`) does not depend on concrete implementations; it depends on contracts (interfaces) located in the `src/Contracts` directory. 

By rebinding these interfaces in your own `AppServiceProvider`, you can seamlessly swap out the default engine logic with your own custom parsers, analyzers, or generators without forking the package.

---

## 2. Building a Custom Analyzer

Analyzers are responsible for converting Laravel code into Data Transfer Objects (DTOs) during the extraction phase. 

Suppose your team doesn't use standard Laravel `FormRequest` classes, but instead uses a custom `DataTransferObject` class for validation. The default `RequestAnalyzer` won't know how to read it.

### Step 1: Implement the Interface
Create a new class that implements `Yab\LaravelApiContract\Contracts\RequestAnalyzerContract`:

```php
namespace App\ApiContract\Analyzers;

use Yab\LaravelApiContract\Contracts\RequestAnalyzerContract;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;

class CustomDtoAnalyzer implements RequestAnalyzerContract
{
    public function analyze(ControllerDefinition $definition): ?RequestDefinition
    {
        // 1. Use Reflection to find your custom DTO class on the controller method
        // 2. Parse the validation rules from your custom DTO
        // 3. Return a RequestDefinition mapped with ValidationField DTOs
        // Return null if no request validation is found.
    }
}
```

### Step 2: Rebind in the Container
In your application's `AppServiceProvider`:

```php
use Yab\LaravelApiContract\Contracts\RequestAnalyzerContract;
use App\ApiContract\Analyzers\CustomDtoAnalyzer;

public function register(): void
{
    $this->app->bind(RequestAnalyzerContract::class, CustomDtoAnalyzer::class);
}
```
Now, when you run `php artisan api-contract:build`, the engine will use your custom analyzer.

---

## 3. Building a New Generator

Generators consume the `ApiContract` object and output artifacts (strings, files, etc.). If you want to generate something new—for example, a **Swift UI API Client** for iOS—you can build a custom generator.

### Step 1: Create the Generator Interface
If you're contributing to the core package, you should create a new interface in `src/Contracts/SwiftGeneratorContract.php`. If this is a local extension, you can implement `Yab\LaravelApiContract\Contracts\GeneratorInterface`.

### Step 2: Implement the Generator

```php
namespace App\ApiContract\Generators;

use Yab\LaravelApiContract\Contracts\ApiContractContract;

class SwiftClientGenerator
{
    public function generate(ApiContractContract $contract): array
    {
        $files = [];

        foreach ($contract->endpoints as $endpoint) {
            $swiftCode = $this->buildSwiftStruct($endpoint);
            
            $files[] = [
                'filename' => "{$endpoint->name}.swift",
                'content' => $swiftCode,
            ];
        }

        return $files;
    }
    
    private function buildSwiftStruct($endpoint): string
    {
        // ... logic to convert $endpoint->resource fields to Swift variables
    }
}
```

### Step 3: Create a Custom Artisan Command
To invoke your generator, create a standard Laravel console command that injects your generator, loads the contract via `ContractSerializer`, and uses `Configuration::ensureSafePath` to write the output.

---

## 4. Extending the Type Mapper

If you need to map PHP types to a different language (or map a custom Laravel type/Enum), you can extend the core Type Mapper logic. Currently, type mappers are implemented inside the respective generators (like `TypeScriptTypeMapper.php`). 

To add a new mapping (e.g., mapping a custom `Carbon` macro to a specific string format), you can intercept the generation phase or submit a PR to extend the core `TypeScriptTypeMapper`.

---

## 5. Adding New Output Formats

If you want to support a new output format (e.g., generating GraphQL schemas or tRPC routers from the REST contract), follow the **Generator** pattern outlined above. 

**Rules for Output Formats:**
1. Generators must be entirely agnostic to Laravel. They should only read properties from the `ApiContract` DTOs.
2. Generators should not perform File I/O. They should return strings or arrays of arrays (`['filename' => '...', 'content' => '...']`) and let the Console command handle disk writing and path security.

---

## 6. Authentication Drivers & Middleware

The current contract definition extracts `middlewares` assigned to a route. Generators (like `SwaggerGenerator` and `ClientGenerator`) look for `auth:sanctum` or `auth:api` in this array to deduce security requirements.

If your application uses a custom authentication middleware (e.g., `auth:jwt` or `custom-api-key`), you can extend the `ContractBuilder` or simply customize the specific generator.

### Example: Modifying the Client Generator for Custom Auth
If contributing to the core, you would modify `ClientGenerator::requiresAuthentication()`:

```php
private function requiresAuthentication(EndpointDefinition $endpoint): bool
{
    foreach ($endpoint->middlewares as $middleware) {
        if (str_starts_with($middleware, 'auth:') || $middleware === 'custom-api-key') {
            return true;
        }
    }
    return false;
}
```

---

## 7. Custom Plugins

While the package doesn't have a formal plugin system, its reliance on the Service Container makes building "plugins" incredibly easy. 

A plugin is simply a Composer package that requires `yab/laravel-api-contract`, ships with a ServiceProvider, and overrides or binds new interfaces.

**Example: A "Spatie Data" Plugin**
If someone wants to add support for `spatie/laravel-data` (which uses DTOs instead of `JsonResource` and `FormRequest`), they could publish a package `yab/laravel-api-contract-spatie`. 

Its ServiceProvider would simply rebind the core analyzers:
```php
$this->app->bind(RequestAnalyzerContract::class, SpatieRequestAnalyzer::class);
$this->app->bind(ResourceAnalyzerContract::class, SpatieResourceAnalyzer::class);
```
Users install the plugin, and the package automatically adapts!

---

## Summary for Contributors

1. **Adhere to the Pipeline:** Code -> Analyzers -> Contract -> Generators -> Output.
2. **Keep the Contract Pure:** The `ApiContract` object is the single source of truth. Do not put generator-specific logic (e.g., "how to render a TypeScript interface") inside the Contract DTOs.
3. **Respect Security:** Always validate user-provided paths with `$configuration->ensureSafePath($path)`.
4. **Program to Interfaces:** Always type-hint Contracts, not concrete implementations, to preserve the extensibility of the ecosystem.
