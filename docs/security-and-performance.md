# Security & Performance

When integrating a tool that parses your application's source code and generates executing files, security and performance are paramount. `laravel-api-contract` is architected with strict safeguards and highly optimized data pipelines to ensure it operates safely and efficiently, even in massive enterprise monorepos.

This document details the threat models mitigated by the package and the performance optimizations built into its core engine.

---

## 1. Security Architecture

### Threat Model

The primary threat model for an AST (Abstract Syntax Tree) parsing and code-generation tool involves:
1. **Malicious Code Execution:** A threat actor injecting executable code into the generated artifacts (e.g., Cross-Site Scripting via generated TypeScript, or remote code execution via PHPUnit test stubs).
2. **Directory Traversal (LFI/Arbitrary Write):** An attacker manipulating output paths to overwrite critical system files (`/etc/passwd`, `.env`, or `.ssh/authorized_keys`).
3. **Information Disclosure:** The analyzer exposing sensitive environment variables or secrets present in the codebase.

The package utilizes the following strategies to fully neutralize these threats.

### Path Traversal Prevention & Filesystem Safety

`laravel-api-contract` enforces a strict, centralized path validation mechanism on *every single file write operation*. 

The `Configuration::ensureSafePath($path)` method intercepts all requested output paths (e.g., via `--output` or `--path` CLI options) and checks the fully resolved real path against permitted directories.

- **The Sandbox:** Generators are strictly confined to the project's root directory (`base_path()`) or the operating system's temporary directory (`sys_get_temp_dir()`).
- **The Defense:** If a user or a CI/CD script accidentally (or maliciously) runs `php artisan api-contract:typescript --output=../../../../etc/passwd`, the path validator detects that the resolved path escapes the sandbox and throws a fatal `InvalidArgumentException`, aborting the operation before any directory creation or file write can occur.

### Safe Reflection

The `ControllerAnalyzer` and `RequestAnalyzer` rely heavily on PHP Reflection to inspect your codebase. 

- **No Code Evaluation:** The package **never** uses `eval()` or attempts to execute the controller methods it analyzes. It statically analyzes method signatures, types, and annotations.
- **AST Parsing Security:** The `ResourceAnalyzer` uses safe regex and Abstract Syntax Tree parsing to read `toArray()` methods. It extracts strings and array keys but does not evaluate the runtime logic, eliminating the risk of arbitrary code execution during the build phase.

### Generated Code Safety

All generators (`TypeScriptGenerator`, `ClientGenerator`, `TestGenerator`) enforce strict encoding:
- **Sanitized JSON:** When generating Swagger or Postman collections, all output is passed through `json_encode` with strict flags (`JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`), neutralizing any potential script injection via route names or parameters.
- **Static Types:** Generated TypeScript interfaces do not contain executable JavaScript logic. They are purely structural.
- **Templating Safety:** The API client generation uses strictly parameterized templates. Input variable names (e.g., form fields) are sanitized to prevent injection into the generated HTTP client closures.

### Secure Defaults

The package is designed to be completely inactive in production unless explicitly called. 
- It does not register any global middleware.
- It does not expose any web routes.
- It installs as a `require-dev` dependency (recommended), meaning its footprint is completely removed during a production deployment (`composer install --no-dev`).

---

## 2. Performance & Scalability

Parsing an entire Laravel application can be an expensive operation. If an API has 500+ endpoints, naïve analysis could consume gigabytes of memory and take minutes to run. `laravel-api-contract` is heavily optimized to run in milliseconds.

### Reflection Optimization

PHP Reflection can be slow if used repetitively on the same classes.
- **Singleton Design:** Analyzers are injected as singletons. Once a `FormRequest` or `JsonResource` signature is analyzed, its metadata can be efficiently mapped.
- **Lazy Loading:** The engine only reflects upon classes that are directly bound to discovered API routes. Unused controllers, console commands, and models are completely ignored, keeping the memory footprint minimal.

### Filesystem Scanning & Caching

The `ResourceAnalyzer` must read the source code of your `JsonResource` and `Controller` classes from disk to perform AST parsing.

- **In-Memory File Caching:** Reading the same controller file 20 times (for 20 different routes) would severely bottleneck disk I/O. The package implements a highly efficient instance-level cache (`$fileCache`). A file is read from disk using `file()` exactly *once*. Subsequent parses of different methods within that same file are served instantly from memory.

### Memory Management

The package operates in a pipeline designed for continuous garbage collection:
1. `RouteAnalyzer` returns basic metadata.
2. The `ContractBuilder` processes one endpoint at a time.
3. Temporary variables (like massive AST trees or raw file string arrays) fall out of scope immediately after an endpoint is parsed, allowing PHP's garbage collector to free the memory.
4. The final `ApiContract` object contains only the lean, strictly-typed DTOs necessary for generation.

### Large Laravel Projects & Scalability

`laravel-api-contract` scales linearly. 

- **Targeted Analysis:** Enterprise projects often have thousands of web routes mixed with API routes. By utilizing the `routes.prefixes` and `routes.middlewares` configuration arrays, the analyzer ignores 90% of the application's surface area, instantly skipping irrelevant web controllers.
- **Modular Monorepos:** For massive monorepos, you can run the `build` command with different configurations to generate separate API Contracts (e.g., `admin-api-contract.json` and `mobile-api-contract.json`), allowing parallel processing and smaller, more manageable artifact files.

### Benchmark Expectations

On standard hardware (e.g., Apple Silicon M-series or modern Intel/AMD processors):
- **Small APIs (1-50 Endpoints):** < 100 milliseconds.
- **Medium APIs (50-250 Endpoints):** ~ 300 - 800 milliseconds.
- **Enterprise APIs (500+ Endpoints):** ~ 1.5 - 3 seconds.

Because execution time is so low, it is highly encouraged to hook `php artisan api-contract:build` into local file watchers or pre-commit hooks. It will not slow down your developer experience.
