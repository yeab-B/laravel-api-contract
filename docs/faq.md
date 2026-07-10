# Frequently Asked Questions (FAQ)

### Do I need to add DocBlocks or Attributes to my controllers?
**No.** The core philosophy of `laravel-api-contract` is "Write once. Generate everything." The package uses static analysis and Abstract Syntax Tree (AST) parsing to read your actual, executing PHP code (specifically your Form Requests and Json Resources). Adding annotations defeats the purpose of having a single source of truth.

### Will this slow down my production application?
**No.** The package is completely inert in production. It registers no global middleware and exposes no web routes. Furthermore, it is highly recommended to install the package as a `--dev` dependency, meaning it won't even exist on your production servers.

### Does the package actually execute my code during analysis?
**No.** The `ControllerAnalyzer` and `RequestAnalyzer` use PHP's Reflection API, which inspects the signatures of your classes without invoking them. The `ResourceAnalyzer` goes a step further by parsing the source code strings directly via AST. Your application state is never modified, and your database is never touched during a build.

### Why is my endpoint not showing up in the generated contract?
By default, the package only scans routes with the `api` prefix. Ensure your route is configured correctly. You can modify which routes are scanned by adjusting the `routes.prefixes` and `routes.middlewares` arrays in your published `config/api-contract.php` file.

### Can I generate an API Client for React/Vue?
**Yes.** The `api-contract:client` command generates a framework-agnostic TypeScript client utilizing modern standard `Fetch` or `Axios`. It can be imported directly into any React, Vue, Svelte, or Vanilla JS project.

### How does the package handle breaking changes?
The package includes a built-in `ContractComparator`. By running `php artisan api-contract:compare --old=old.json --new=new.json`, the package performs a deep structural diff. If it detects a backwards-incompatible change (like removing a field or making an optional parameter required), it will alert you and fail the command, allowing you to block the deployment in your CI/CD pipeline.

### What if my `toArray()` method in my Resource has highly dynamic, conditional logic?
The AST parser is highly optimized for standard, idiomatic array returns. If you have extremely complex dynamic logic (e.g., merging multiple arrays based on complex state loops), the parser might fall back to a generic `mixed` type representation. We recommend keeping your API Resources as declarative as possible for maximum type-safety.

### Can I write a custom generator?
**Absolutely.** The package is strictly bound via Interfaces and the Laravel Service Container. You can write your own class implementing `GeneratorInterface`, inject the `ApiContract` object, and output any language or format you desire (e.g., Swift, Kotlin, GraphQL schemas). See the [Extending Guide](extending.md) for details.
