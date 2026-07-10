# API Version Comparison & Semantic Versioning

One of the most critical challenges in API development is preventing breaking changes. A breaking change occurs when an API modification breaks an existing client's integration (e.g., a mobile app crashes because a field it expected was removed).

`laravel-api-contract` provides a robust, programmatic way to detect these changes *before* they are deployed.

---

## 1. The Contract Comparator

The package features a built-in `ContractComparator` engine. This engine takes two compiled API Contracts (an "old" baseline and a "new" build) and performs a deep structural diff.

You can trigger this engine using the Artisan command:

```bash
php artisan api-contract:compare --old=storage/v1-contract.json --new=storage/v2-contract.json
```

### Breaking Change Detection

The engine meticulously inspects the differences between the two contracts and categorizes them.

**What is considered a BREAKING change?**
- Removing an existing endpoint.
- Changing the HTTP method of an existing endpoint (e.g., `PUT` to `PATCH`).
- Adding a *required* parameter to a request payload.
- Changing a previously optional parameter to *required*.
- Removing a field from a JSON response payload.
- Changing a response field from *guaranteed* to *nullable*.
- Changing the data type of an input or output field.

**What is considered a NON-BREAKING change?**
- Adding a brand-new endpoint.
- Adding a new *optional* parameter to a request payload.
- Adding a new field to a JSON response payload.
- Changing a required request parameter to optional.

If *any* breaking changes are detected, the command exits with a failure code (`1`). This allows you to easily block deployments in your CI/CD pipelines (like GitHub Actions) if a developer accidentally introduces a breaking change.

---

## 2. Generating Change Reports

The comparator can output its findings in various formats.

**CLI Output (Default):**
Provides a color-coded summary directly in your terminal.

**JSON Output:**
Useful for programmatic consumption or custom dashboarding.
```bash
php artisan api-contract:compare --old=old.json --new=new.json --format=json --output=report.json
```

**Markdown Output:**
Generates a beautiful Markdown document categorizing the changes. This is excellent for attaching to GitHub Pull Requests or generating automated changelogs for your API consumers.
```bash
php artisan api-contract:compare --old=old.json --new=new.json --format=markdown --output=changelog.md
```

---

## 3. Semantic Versioning Integration

To effectively manage API evolution, you should tie your `ApiContract` to Semantic Versioning (SemVer).

In your `config/api-contract.php`, you can define the current version:

```php
'contract' => [
    'version' => env('API_VERSION', '1.0.0'),
],
```

**Best Practice Workflow:**
1. **Patch (`1.0.1`):** Use for internal bug fixes that do not change the API Contract at all.
2. **Minor (`1.1.0`):** Use when the `api-contract:compare` command detects only **NON-BREAKING** changes (e.g., you added a new endpoint or an optional parameter).
3. **Major (`2.0.0`):** Use when the `api-contract:compare` command detects **BREAKING** changes. A major version bump signals to your consumers that they need to update their integrations.

By utilizing the comparison engine alongside SemVer, you replace human guesswork with cryptographic certainty regarding the stability of your API.
