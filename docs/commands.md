# Artisan Commands Reference

The `laravel-api-contract` package exposes a suite of powerful Artisan commands to build, inspect, and generate artifacts from your API. 

This document serves as a comprehensive reference for every command available.

---

## 1. `api-contract:build`

**Purpose:** 
Scans your Laravel application, extracts all route, request, and resource metadata, and compiles it into the master `ApiContract` JSON file. This is the prerequisite command for all other generators.

**Syntax:** 
`php artisan api-contract:build [options]`

**Options:**
- `--path=` (string): Override the output path for the JSON contract file. Defaults to `storage/api-contract.json`.

**Example Usage:**
```bash
php artisan api-contract:build
```

**Expected Output:**
```text
Building API Contract...
Extracted 42 endpoints.
API Contract successfully written to: /var/www/html/storage/api-contract.json
```

**Common Errors:**
- *Path traversal detected:* Thrown if you provide a `--path` that resolves outside your project directory.
- *Failed to serialize contract:* Usually caused by malformed UTF-8 characters returned from a static analyzer.

**Best Practices:**
- Run this command automatically in your local development environment using a file watcher, or as a pre-commit hook, to ensure the contract is always in sync with your code.

---

## 2. `api-contract:compare`

**Purpose:** 
Compares two distinct `api-contract.json` files to detect structural changes and identify backwards-incompatible (breaking) changes.

**Syntax:** 
`php artisan api-contract:compare --old=<path> --new=<path> [options]`

**Options:**
- `--old=` (required string): The path to the previously deployed baseline contract.
- `--new=` (required string): The path to the newly built contract.
- `--format=` (string): Output format (`cli`, `markdown`, `json`). Defaults to `cli`.
- `--output=` (string): Path to save the report file (only applicable if format is markdown or json).

**Example Usage:**
```bash
php artisan api-contract:compare --old=storage/old.json --new=storage/new.json --format=markdown --output=docs/changes.md
```

**Expected Output (CLI):**
```text
Comparing API contracts...
🔴 BREAKING CHANGE: Removed field `email` from `UserResource`.
🟢 NON-BREAKING: Added optional query parameter `sort` to `IndexUsersRequest`.

Total Changes: 2
Status: Failed (Breaking Changes Detected)
```

**Common Errors:**
- *Old/New contract file not found:* Ensure the paths provided exist. 
- *Both --old and --new options are required.*

**Best Practices:**
- Run this as a mandatory step in your CI/CD pipeline (e.g., GitHub Actions). If the command exits with `1` (Failure), block the pull request.

---

## 3. `api-contract:swagger`

**Purpose:** 
Generates a valid OpenAPI v3.0 JSON specification from your compiled API Contract.

**Syntax:** 
`php artisan api-contract:swagger [options]`

**Options:**
- `--path=` (string): The path to save the generated `swagger.json` file.
- `--pretty` (flag): Format the JSON with indentation and line breaks for human readability.

**Example Usage:**
```bash
php artisan api-contract:swagger --path=public/docs/swagger.json --pretty
```

**Best Practices:**
- Output directly to your `public` directory so Swagger UI or ReDoc can host the documentation seamlessly on your live server.

---

## 4. `api-contract:typescript`

**Purpose:** 
Generates TypeScript interfaces that strictly mirror your backend Form Requests (inputs) and Json Resources (outputs).

**Syntax:** 
`php artisan api-contract:typescript [options]`

**Options:**
- `--output=` (string): The path to save the generated file(s). If omitted, prints to the console.

**Example Usage:**
```bash
php artisan api-contract:typescript --output=resources/ts/types/api.ts
```

**Best Practices:**
- Point this directly into your frontend asset directory. Import these generated interfaces into your frontend components to achieve end-to-end type safety.

---

## 5. `api-contract:client`

**Purpose:** 
Generates a robust, fully-typed TypeScript HTTP client (using Fetch or Axios paradigms) complete with methods for every endpoint and automatic authentication interceptors.

**Syntax:** 
`php artisan api-contract:client [options]`

**Options:**
- `--output=` (string): The directory path where the client service files should be generated.

**Example Usage:**
```bash
php artisan api-contract:client --output=resources/ts/services/
```

**Expected Output:**
Generates files like `resources/ts/services/UserService.ts` and `resources/ts/services/PostService.ts`.

**Best Practices:**
- Use this command to completely eliminate boilerplate HTTP wrapping in your frontend applications.

---

## 6. `api-contract:tests`

**Purpose:** 
Scaffolds boilerplate PHPUnit feature tests for every API endpoint. It pre-populates dummy request payloads based on Form Requests and assertion stubs based on Resource outputs.

**Syntax:** 
`php artisan api-contract:tests [options]`

**Options:**
- `--output=` (string): The directory to save the test files.

**Example Usage:**
```bash
php artisan api-contract:tests --output=tests/Feature/Api/
```

**Common Errors:**
- *Failed to write file:* Ensure the target test directory is writable and the path resolves securely within your project.

**Best Practices:**
- Run this *once* when initially building a new controller to get a massive head start on Test-Driven Development (TDD).

---

## 7. `api-contract:postman`

**Purpose:** 
Generates a Postman Collection v2.1 JSON file, grouped by controller, ready for immediate import into Postman.

**Syntax:** 
`php artisan api-contract:postman [options]`

**Options:**
- `--path=` (string): The path to write the Postman JSON file.
- `--pretty` (flag): Format the output JSON.

**Example Usage:**
```bash
php artisan api-contract:postman --path=storage/postman.json --pretty
```

**Best Practices:**
- Import this generated file into your shared team Postman workspace whenever new routes are added, ensuring QA and third-party consumers have up-to-date execution environments.

---

## 8. `api-contract:docs`

**Purpose:** 
Generates beautiful, human-readable Markdown documentation containing endpoint summaries, tables of parameters, and example JSON responses.

**Syntax:** 
`php artisan api-contract:docs [options]`

**Options:**
- `--path=` (string): The path to save the Markdown file.

**Example Usage:**
```bash
php artisan api-contract:docs --path=docs/api-reference.md
```

**Best Practices:**
- Hook this command into a static site generator (like GitBook, VuePress, or Docusaurus) deployment workflow to auto-publish public API documentation.

---

## 9. Debugging & Utility Commands

If you ever need to inspect what the internal analyzers are "seeing" before they build the final contract, use these utility commands:

- **`api-contract:routes`**: Prints a table of all discovered API routes after configuration filters are applied.
- **`api-contract:controllers`**: Prints the controllers and methods bound to the discovered routes.
- **`api-contract:requests`**: Dumps the raw validation rules extracted from Form Requests.
- **`api-contract:resources`**: Dumps the Abstract Syntax Tree (AST) parsed array structures of your Json Resources.

*These commands take no special options and output directly to the console in real-time.*
