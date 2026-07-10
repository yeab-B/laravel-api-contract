<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Yab\LaravelApiContract\Analyzers\ControllerAnalyzer;
use Yab\LaravelApiContract\Analyzers\RequestAnalyzer;
use Yab\LaravelApiContract\Analyzers\ResourceAnalyzer;
use Yab\LaravelApiContract\Analyzers\RouteAnalyzer;
use Yab\LaravelApiContract\Config\Configuration;
use Yab\LaravelApiContract\Console\InstallCommand;
use Yab\LaravelApiContract\Console\RoutesCommand;
use Yab\LaravelApiContract\Console\ControllersCommand;
use Yab\LaravelApiContract\Console\RequestsCommand;
use Yab\LaravelApiContract\Console\ResourcesCommand;
use Yab\LaravelApiContract\Console\BuildCommand;
use Yab\LaravelApiContract\Console\CompareCommand;
use Yab\LaravelApiContract\Console\SwaggerCommand;
use Yab\LaravelApiContract\Console\TypeScriptCommand;
use Yab\LaravelApiContract\Console\ClientCommand;
use Yab\LaravelApiContract\Console\PostmanCommand;
use Yab\LaravelApiContract\Console\MarkdownCommand;
use Yab\LaravelApiContract\Console\TestCommand;
use Yab\LaravelApiContract\Contracts\ClientGeneratorContract;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Contracts\ContractComparatorContract;
use Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract;
use Yab\LaravelApiContract\Contracts\MarkdownGeneratorContract;
use Yab\LaravelApiContract\Contracts\PostmanGeneratorContract;
use Yab\LaravelApiContract\Contracts\RequestAnalyzerContract;
use Yab\LaravelApiContract\Contracts\TestGeneratorContract;
use Yab\LaravelApiContract\Contracts\ResourceAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;
use Yab\LaravelApiContract\Contracts\SwaggerGeneratorContract;
use Yab\LaravelApiContract\Contracts\TypeScriptGeneratorContract;
use Yab\LaravelApiContract\Generators\Swagger\SchemaGenerator;
use Yab\LaravelApiContract\Generators\Swagger\SwaggerGenerator;
use Yab\LaravelApiContract\Generators\TypeScript\TypeScriptGenerator;
use Yab\LaravelApiContract\Generators\Client\ClientGenerator;
use Yab\LaravelApiContract\Generators\Postman\PostmanGenerator;
use Yab\LaravelApiContract\Generators\Markdown\MarkdownGenerator;
use Yab\LaravelApiContract\Generators\Test\TestGenerator;
use Yab\LaravelApiContract\Services\Client\ClientBuilder;
use Yab\LaravelApiContract\Services\Comparison\ContractComparator;
use Yab\LaravelApiContract\Services\ContractBuilder;
use Yab\LaravelApiContract\Services\OpenApi\OpenApiBuilder;
use Yab\LaravelApiContract\Services\Markdown\MarkdownBuilder;
use Yab\LaravelApiContract\Services\Postman\PostmanBuilder;
use Yab\LaravelApiContract\Services\Test\TestBuilder;
use Yab\LaravelApiContract\Services\TypeScript\TypeScriptBuilder;
use Yab\LaravelApiContract\Support\ContractSerializer;
use Yab\LaravelApiContract\Support\ResourceParser;
use Yab\LaravelApiContract\Support\TypeScriptTypeMapper;
use Yab\LaravelApiContract\Support\ValidationRuleParser;

class LaravelApiContractServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/api-contract.php',
            'api-contract',
        );

        $this->app->singleton(Configuration::class, function ($app): Configuration {
            return new Configuration($app['config']);
        });

        $this->app->bind(RouteAnalyzerContract::class, function ($app): RouteAnalyzer {
            return new RouteAnalyzer(
                router: $app->make(Router::class),
                configuration: $app->make(Configuration::class),
            );
        });

        $this->app->bind(ControllerAnalyzerContract::class, ControllerAnalyzer::class);

        $this->app->bind(RequestAnalyzerContract::class, function ($app): RequestAnalyzer {
            return new RequestAnalyzer(
                parser: $app->make(ValidationRuleParser::class),
            );
        });

        $this->app->singleton(ResourceParser::class);

        $this->app->bind(ResourceAnalyzerContract::class, function ($app): ResourceAnalyzer {
            return new ResourceAnalyzer(
                parser: $app->make(ResourceParser::class),
            );
        });

        $this->app->singleton(ContractSerializer::class);

        $this->app->bind(ContractComparatorContract::class, ContractComparator::class);

        $this->app->bind(ContractBuilderContract::class, function ($app): ContractBuilder {
            return new ContractBuilder(
                routeAnalyzer: $app->make(RouteAnalyzerContract::class),
                controllerAnalyzer: $app->make(ControllerAnalyzerContract::class),
                requestAnalyzer: $app->make(RequestAnalyzerContract::class),
                resourceAnalyzer: $app->make(ResourceAnalyzerContract::class),
                configuration: $app->make(Configuration::class),
            );
        });

        $this->app->singleton(SchemaGenerator::class);

        $this->app->singleton(OpenApiBuilder::class, function ($app): OpenApiBuilder {
            return new OpenApiBuilder(
                schemaGenerator: $app->make(SchemaGenerator::class),
            );
        });

        $this->app->bind(SwaggerGeneratorContract::class, function ($app): SwaggerGenerator {
            return new SwaggerGenerator(
                builder: $app->make(OpenApiBuilder::class),
            );
        });

        $this->app->singleton(TypeScriptTypeMapper::class);
        $this->app->singleton(TypeScriptBuilder::class);

        $this->app->bind(TypeScriptGeneratorContract::class, function ($app): TypeScriptGenerator {
            return new TypeScriptGenerator(
                builder: $app->make(TypeScriptBuilder::class),
                mapper: $app->make(TypeScriptTypeMapper::class),
            );
        });

        $this->app->singleton(ClientBuilder::class);

        $this->app->bind(ClientGeneratorContract::class, function ($app): ClientGenerator {
            return new ClientGenerator(
                builder: $app->make(ClientBuilder::class),
                mapper: $app->make(TypeScriptTypeMapper::class),
            );
        });

        $this->app->singleton(PostmanBuilder::class);

        $this->app->bind(PostmanGeneratorContract::class, function ($app): PostmanGenerator {
            return new PostmanGenerator(
                builder: $app->make(PostmanBuilder::class),
            );
        });

        $this->app->singleton(MarkdownBuilder::class);

        $this->app->bind(MarkdownGeneratorContract::class, function ($app): MarkdownGenerator {
            return new MarkdownGenerator(
                builder: $app->make(MarkdownBuilder::class),
            );
        });

        $this->app->singleton(TestBuilder::class);

        $this->app->bind(TestGeneratorContract::class, function ($app): TestGenerator {
            return new TestGenerator(
                builder: $app->make(TestBuilder::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/api-contract.php' => $this->app->configPath('api-contract.php'),
            ], 'api-contract-config');

            $this->commands([
                InstallCommand::class,
                RoutesCommand::class,
                ControllersCommand::class,
                RequestsCommand::class,
                ResourcesCommand::class,
                BuildCommand::class,
                CompareCommand::class,
                SwaggerCommand::class,
                TypeScriptCommand::class,
                ClientCommand::class,
                PostmanCommand::class,
                MarkdownCommand::class,
                TestCommand::class,
            ]);
        }
    }
}
