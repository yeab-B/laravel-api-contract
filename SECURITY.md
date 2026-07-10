# Security Policy

## Supported Versions

The following versions of `laravel-api-contract` are currently receiving security updates:

| Version | Supported          |
|---------|--------------------|
| 1.x     | ✅ Yes             |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security-related issue, please **do not open a public GitHub issue**. Instead, please report it responsibly.

### How to Report

Please email **security@example.com** with:

- A clear description of the vulnerability.
- Steps to reproduce the issue.
- The potential impact of the vulnerability.
- Any suggested fixes or mitigations (if known).

### What to Expect

- **Acknowledgement:** You will receive an acknowledgement within **48 hours**.
- **Status Updates:** We will provide a status update within **7 days** while we investigate.
- **Resolution:** We aim to release a fix within **30 days** for critical vulnerabilities.
- **Credit:** With your permission, we will acknowledge your responsible disclosure in the release notes.

## Security Considerations

### Path Traversal Prevention

The package includes built-in protection against path traversal attacks via `Configuration::ensureSafePath()`. All file write operations are validated to ensure they remain within the application's base directory or the system's temporary directory.

### Trust Model

This package is a **developer tool** and is designed to be installed as a dev dependency (`composer require --dev`). It should **not** be deployed to production environments unless you have a specific, controlled use case. The Artisan commands analyze your application's codebase and write files to disk.

### No External Requests

This package makes **no external network requests** at runtime. All analysis is performed locally using PHP Reflection and file parsing.

### Generated Output

The generated artifacts (JSON files, TypeScript files, etc.) may contain structural information about your API, including endpoint URIs and validation rules. Treat these files appropriately and do not commit sensitive data to public repositories.

## Scope

The following are **in scope** for security reports:

- Path traversal vulnerabilities in file write operations.
- Arbitrary code execution via malicious input to analyzers.
- Information disclosure from the generated contract files.

The following are **out of scope**:

- Vulnerabilities in dependencies (report those to the respective upstream projects).
- Issues in the user's application code that is analyzed by this package.
- Social engineering attacks.
