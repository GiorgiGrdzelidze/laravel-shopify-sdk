<?php

declare(strict_types=1);

namespace LaravelShopifySdk;

use LaravelShopifySdk\Auth\HmacValidator;
use LaravelShopifySdk\Auth\OAuthManager;
use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Clients\RestClient;
use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Commands\SetupStoreCommand;
use LaravelShopifySdk\Commands\SyncAllCommand;
use LaravelShopifySdk\Commands\SyncCustomersCommand;
use LaravelShopifySdk\Commands\SyncInventoryCommand;
use LaravelShopifySdk\Commands\SyncOrdersCommand;
use LaravelShopifySdk\Commands\SyncProductsCommand;
use LaravelShopifySdk\Commands\SyncStoresCommand;
use LaravelShopifySdk\Commands\SyncCollectionsCommand;
use LaravelShopifySdk\Commands\AssignSuperAdminCommand;
use LaravelShopifySdk\Sync\SyncRunner;
use LaravelShopifySdk\Webhooks\WebhookVerifier;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Shopify SDK Service Provider
 *
 * Registers all package services, commands, routes, migrations, and configurations.
 * Supports optional Filament v5 integration when enabled in config.
 *
 * @package LaravelShopifySdk
 */
class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/shopify.php',
            'shopify'
        );

        $this->registerCoreServices();
        $this->registerFilamentServices();
    }

    /**
     * Bootstrap package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishAssets();
        $this->loadMigrations();
        $this->loadRoutes();
        $this->loadTranslations();
        $this->loadViews();
        $this->registerCommands();
        $this->bootFilament();
    }

    /**
     * Load package views.
     *
     * @return void
     */
    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'shopify');
    }

    /**
     * Boot Filament resources and widgets.
     *
     * @return void
     */
    protected function bootFilament(): void
    {
        if (!config('shopify.filament.enabled', false)) {
            return;
        }

        if (!class_exists(\Filament\Panel::class)) {
            return;
        }

        // Filament v5 uses panel-based discovery
        // Resources and widgets will be auto-discovered by the panel
        // if they are in the correct namespace and properly configured
    }

    /**
     * Register core package services.
     *
     * @return void
     */
    protected function registerCoreServices(): void
    {
        $this->app->singleton(HmacValidator::class, function ($app) {
            return new HmacValidator();
        });

        $this->app->singleton(WebhookVerifier::class, function ($app) {
            return new WebhookVerifier(
                config('shopify.webhooks.secret'),
                $app->make(HmacValidator::class)
            );
        });

        $this->app->singleton(StoreRepository::class, function ($app) {
            return new StoreRepository();
        });

        $this->app->singleton(OAuthManager::class, function ($app) {
            return new OAuthManager(
                $app->make(StoreRepository::class),
                $app->make(HmacValidator::class)
            );
        });

        $this->app->bind(GraphQLClient::class, function ($app) {
            return new GraphQLClient();
        });

        $this->app->bind(RestClient::class, function ($app) {
            return new RestClient();
        });

        $this->app->singleton(ShopifyClient::class, function ($app) {
            return new ShopifyClient(
                $app->make(GraphQLClient::class),
                $app->make(RestClient::class),
                $app->make(StoreRepository::class)
            );
        });

        $this->app->singleton(SyncRunner::class, function ($app) {
            return new SyncRunner(
                $app->make(ShopifyClient::class)
            );
        });
    }

    /**
     * Register Filament v5 services if enabled.
     *
     * Registers Shopify resources and widgets with Filament panels.
     *
     * @return void
     */
    protected function registerFilamentServices(): void
    {
        if (!config('shopify.filament.enabled', false)) {
            return;
        }

        if (!class_exists(\Filament\Panel::class)) {
            return;
        }

        // Register Filament resources and widgets
        \Filament\Support\Facades\FilamentAsset::register([
            // Assets will be auto-discovered
        ]);
    }

    /**
     * Publish package assets.
     *
     * @return void
     */
    protected function publishAssets(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/shopify.php' => config_path('shopify.php'),
            ], 'shopify-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'shopify-migrations');

            $this->publishes([
                __DIR__.'/../resources/lang' => $this->app->langPath('vendor/shopify'),
            ], 'shopify-lang');
        }
    }

    /**
     * Load package migrations.
     *
     * @return void
     */
    protected function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Load package routes.
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Load package translations.
     *
     * @return void
     */
    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'shopify');
    }

    /**
     * Register package commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SetupStoreCommand::class,
                SyncStoresCommand::class,
                SyncProductsCommand::class,
                SyncOrdersCommand::class,
                SyncCustomersCommand::class,
                SyncInventoryCommand::class,
                SyncCollectionsCommand::class,
                SyncAllCommand::class,
                AssignSuperAdminCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            HmacValidator::class,
            WebhookVerifier::class,
            StoreRepository::class,
            OAuthManager::class,
            GraphQLClient::class,
            RestClient::class,
            ShopifyClient::class,
            SyncRunner::class,
        ];
    }
}
