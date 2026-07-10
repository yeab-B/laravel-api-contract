# Contributing to Laravel API Contract

Thank you for considering contributing to **Laravel API Contract**! We welcome all contributions, whether they're bug reports, feature suggestions, documentation improvements, or pull requests.

## 📋 Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md). Please read it before contributing.

## 🐛 Reporting Bugs

Before submitting a bug report:

1. **Search existing issues** to see if the bug has already been reported.
2. **Ensure you are using a supported version** of PHP (8.2+) and Laravel (11+).

When submitting a bug report, please include:

- A clear and descriptive title.
- A detailed description of the problem.
- Steps to reproduce the issue.
- The expected behavior.
- The actual behavior.
- Your PHP version (`php -v`), Laravel version, and package version.
- Any relevant code snippets or error messages.

## 💡 Suggesting Features

We love feature suggestions! Please open a GitHub Issue using the feature request template and include:

- A clear description of the proposed feature.
- The problem it solves or the use case it enables.
- Any examples of how it might work.

## 🔧 Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer 2.x

### Installation

```bash
# Clone the repository
git clone https://github.com/yeab-B/laravel-api-contract.git
cd laravel-api-contract

# Install dependencies
composer install
```

### Running Tests

```bash
# Run the full test suite
composer test

# Run with coverage report
composer test-coverage
```

### Code Style

We enforce PSR-12 coding standards across all source files:

```bash
# Check code style
composer lint

# Automatically fix code style issues
composer lint-fix
```

### Static Analysis

We use PHPStan at the **max** level. All code must pass with 0 errors:

```bash
composer analyse
```

## 📝 Pull Request Process

1. **Fork** the repository and create a new branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Write code** following these guidelines:
   - Follow PSR-12 coding standards.
   - Add `declare(strict_types=1)` to every new PHP file.
   - Use PHP 8.2+ features where appropriate (readonly properties, named arguments, etc.).
   - Follow SOLID principles and keep classes focused.
   - Add PHPDoc blocks for all public methods.

3. **Write tests** for any new functionality:
   - Unit tests should cover individual classes in isolation.
   - Feature tests should cover Artisan commands end-to-end.
   - Ensure all existing tests continue to pass.

4. **Run all checks** before submitting:
   ```bash
   composer test && composer lint && composer analyse
   ```

5. **Update documentation** if your change affects the public API, configuration options, or command behavior.

6. **Submit your Pull Request** with:
   - A clear title describing the change.
   - A description of what was changed and why.
   - Reference any related issues (e.g., `Closes #42`).

## 🏗️ Project Architecture

Before contributing, familiarize yourself with the project structure:

```
src/
├── Analyzers/          # Route, Controller, Request, Resource analyzers
├── Config/             # Configuration wrapper
├── Console/            # Artisan commands
├── Contracts/          # PHP interfaces (abstractions)
├── Generators/         # Output generators (Swagger, TypeScript, etc.)
├── Providers/          # Laravel service provider
├── Services/           # Domain services and DTOs
│   ├── Client/         # API client builder
│   ├── Comparison/     # Contract comparator
│   ├── Contract/       # Core ApiContract and EndpointDefinition
│   ├── DTO/            # Data Transfer Objects
│   ├── Markdown/       # Markdown builder
│   └── Test/           # Test builder
└── Support/            # Facades, helpers, serializers
```

For a full architectural overview, see [docs/architecture.md](docs/architecture.md).

## ✅ Checklist Before Submitting

- [ ] Tests pass: `composer test`
- [ ] No lint errors: `composer lint`
- [ ] No PHPStan errors: `composer analyse`
- [ ] New code has test coverage
- [ ] Documentation is updated if needed
- [ ] The `CHANGELOG.md` has an entry under `[Unreleased]`

## 📄 License

By contributing to Laravel API Contract, you agree that your contributions will be licensed under the [MIT License](LICENSE).
