# Future Roadmap

`laravel-api-contract` is under active development. Our goal is to continuously expand the extraction capabilities of the engine and support more modern frontend paradigms.

Here is the current roadmap for future releases:

## 1. DTO Support (Spatie Data Integration)
Currently, the analyzer relies on Laravel's standard `FormRequest` and `JsonResource` classes. However, many modern Laravel teams are adopting DTO (Data Transfer Object) packages, specifically `spatie/laravel-data`, which merges requests and resources into a single class.
- **Goal:** Build native analyzers capable of parsing Spatie Data objects to extract validation rules and response schemas seamlessly.

## 2. Enum Parsing Support
PHP 8.1 introduced native Enums. While the package currently handles string and integer types well, recognizing Enums and carrying their specific allowed values through to the generated artifacts (like OpenAPI `enum` properties and TypeScript union types) will vastly improve type safety.
- **Goal:** Intercept Enum casts in Form Requests and Json Resources to generate strict Enum definitions in Swagger and TypeScript.

## 3. Nuxt 3 & Vue 3 Composables Generation
The current `api-contract:client` generates agnostic Axios/Fetch classes. We want to support framework-specific generation.
- **Goal:** Create a generator that outputs Vue 3 `useFetch` composables explicitly tailored for Nuxt 3 applications, providing SSR-friendly data fetching with full type inference.

## 4. GraphQL Schema Generation
While this package focuses on REST architectures, the underlying `ApiContract` intermediate representation is highly structured.
- **Goal:** Explore generating a boilerplate GraphQL Schema definition directly from the REST contract, bridging the gap between REST controllers and GraphQL consumers.

## 5. Webhook Contract Support
APIs aren't just about incoming requests; they also push data out via Webhooks.
- **Goal:** Introduce a mechanism to analyze internal Laravel Events/Listeners and generate contract definitions for outbound Webhook payloads, ensuring third-party consumers have typed definitions of the webhooks they receive.

---

*If you would like to contribute to any of these roadmap items, please check our GitHub issues page or submit a proposal PR!*
