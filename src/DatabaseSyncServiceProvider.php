<?php

namespace Obalaweb\LaravelDatabaseSync;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Obalaweb\LaravelDatabaseSync\Services\DatabaseSyncService;
use Obalaweb\LaravelDatabaseSync\Services\ModelDiscoveryService;
use Obalaweb\LaravelDatabaseSync\Services\AutoSyncService;
use Obalaweb\LaravelDatabaseSync\Commands\DatabaseSyncCommand;

class DatabaseSyncServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services
        $this->app->singleton(DatabaseSyncService::class);
        $this->app->singleton(ModelDiscoveryService::class);
        $this->app->singleton(AutoSyncService::class);

        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/database-sync.php', 'database-sync');
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/config/database-sync.php' => config_path('database-sync.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DatabaseSyncCommand::class,
            ]);
        }

        // Auto-setup sync if enabled
        if (config('database-sync.enabled', false)) {
            $this->setupAutoSync();
        }
    }

    private function setupAutoSync()
    {
        $autoSync = $this->app->make(AutoSyncService::class);

        // Use defer to ensure models are loaded
        $this->app->booted(function () use ($autoSync) {
            $autoSync->registerAllModels();
        });
    }
}
