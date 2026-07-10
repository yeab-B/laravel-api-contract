# Introduction to API Contracts

Before diving into the mechanics of the `laravel-api-contract` package, it is essential to understand the underlying concept it enforces: **The API Contract**.

This document will explore what API contracts are, why they are a critical component of modern software development, and how `laravel-api-contract` drastically simplifies adopting them in your workflow.

---

## What is an API Contract?

An API Contract is a formal, machine-readable agreement between an API provider (the backend) and an API consumer (the frontend, mobile app, or external service). 

It explicitly defines:
1. **The Endpoints:** The URLs and HTTP methods available.
2. **The Inputs:** The required and optional parameters, query strings, headers, and request body structures (including data types and validation constraints).
3. **The Outputs:** The exact shape, structure, and data types of the JSON payloads returned by the server.
4. **The Errors:** The expected HTTP status codes and error formats.

Think of it as a strict architectural blueprint. Just as a contractor wouldn't build a house without a blueprint agreed upon by the architect, frontend and backend teams shouldn't build integrations without an agreed-upon API Contract.

## Why API Contracts Matter

In modern software development, backends and frontends are decoupled. A backend team might build a REST API in Laravel, while a separate frontend team consumes it using React, Vue, or iOS/Android native code.

Because these systems are decoupled, they must communicate across a boundary. If the backend changes a field name from `user_id` to `userId` without warning, the frontend breaks. If the backend marks a previously optional field as `required`, the mobile app will suddenly fail to submit forms. 

API Contracts matter because they **codify the boundary**. They provide a single source of truth that guarantees both sides understand exactly how data is structured and exchanged.

## Why Modern Projects Need a Contract

Modern projects heavily utilize strict typing systems like **TypeScript** on the frontend. TypeScript provides immense safety and developer experience (DX) by catching errors at compile-time rather than run-time.

However, TypeScript is only as good as the types you feed it. If a frontend developer manually writes a TypeScript interface that guesses the shape of a Laravel API response, the type safety is an illusion. When the Laravel API changes, the TypeScript interface remains the same, leading to production crashes that the compiler didn't catch.

To achieve true, end-to-end type safety across the network boundary, the frontend's TypeScript interfaces must be generated directly from a strict API Contract.

## The Traditional API Development Workflow

Historically, maintaining an API contract has been a highly manual and error-prone process:

1. The backend developer writes a Laravel controller, a Form Request for validation, and a Resource for the response.
2. The backend developer then manually writes an OpenAPI (Swagger) YAML file or adds heavy PHP docblock annotations to describe what they just wrote.
3. The backend developer opens Postman, creates a new request, fills in example data, and saves it to a shared workspace.
4. The frontend developer looks at the Swagger docs (or Postman) and manually translates the JSON structures into TypeScript interfaces.
5. The frontend developer manually writes an Axios or Fetch client to make the HTTP requests.

## The Problem with Manual Synchronization

The traditional workflow is fundamentally flawed due to **Synchronization Drift**:

- **It violates DRY (Don't Repeat Yourself):** The backend developer defines validation rules in Laravel, then repeats them in Swagger annotations.
- **It is highly prone to human error:** Developers forget to update the Swagger file when they add a new field to a Laravel Resource.
- **It creates friction:** Frontend developers are blocked waiting for backend developers to update Postman collections or documentation.
- **It leads to broken integrations:** Because the contract (Swagger) is decoupled from the actual execution code (Laravel), they eventually fall out of sync. The documentation becomes a lie, and the frontend crashes.

## How `laravel-api-contract` Solves These Problems

`laravel-api-contract` flips the traditional workflow on its head. 

Instead of forcing developers to manually maintain documentation and contracts alongside their code, the package **derives the contract directly from the code.**

By using advanced static analysis (Reflection and Abstract Syntax Tree parsing), the package inspects your actual, executing Laravel code:
- It looks at your registered `routes`.
- It reads your `FormRequest` validation `rules()` to understand the inputs.
- It analyzes your `JsonResource` `toArray()` methods to understand the outputs.

It then generates a unified, machine-readable API Contract. From that contract, it automatically generates:
- OpenAPI/Swagger Documentation
- TypeScript Interfaces
- Typed API Clients (Fetch/Axios)
- Postman Collections
- PHPUnit Feature Tests
- Markdown Documentation

## The Core Philosophy

**"Write once. Generate everything."**

The philosophy of `laravel-api-contract` is that your Laravel codebase should be the *only* source of truth. You should not have to write documentation annotations. You should not have to maintain Postman collections. You should not have to manually sync TypeScript interfaces.

You write standard, idiomatic Laravel code. The package handles the rest.

## Real-World Examples

### Example A: Adding a new feature
A product manager requests a new `avatar` field for user profiles. 
1. The backend developer adds `'avatar' => 'nullable|image'` to the `UpdateProfileRequest`.
2. The backend developer adds `'avatar' => $this->avatar_url` to the `UserProfileResource`.
3. They run `php artisan api-contract:build`.
4. **Instantly:** The Swagger docs show the new field. The frontend team receives a new TypeScript interface with `avatar?: string`. The Postman collection is updated. No manual coordination was required.

### Example B: Preventing a breaking change
A backend developer decides to rename the `email` field in a response to `email_address` to match a new database convention.
1. They make the change in the `UserResource` and run `php artisan api-contract:compare --old=previous-contract.json --new=new-contract.json`.
2. The package immediately alerts them: **BREAKING CHANGE DETECTED: Field `email` was removed from `UserResource`.**
3. The developer realizes this will break the current mobile app in production and decides to retain the `email` field for backwards compatibility, averting an outage.

## Who Should Use This Package?

- **Full-Stack Teams:** Teams building Laravel backends with Vue/React/Svelte/Angular frontends who want seamless, end-to-end type safety without the overhead of GraphQL or tRPC.
- **API Providers:** Companies providing REST APIs to third parties who want to guarantee their OpenAPI specifications are always 100% accurate.
- **Mobile App Teams:** Teams building iOS and Android apps backed by Laravel who need a strict contract to prevent breaking changes.
- **Agile Teams:** Developers who want to eliminate the chore of writing and maintaining API documentation manually.
