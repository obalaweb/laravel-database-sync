<?php

namespace LaravelDatabaseSync\Commands;

use Illuminate\Console\Command;
use LaravelDatabaseSync\Services\ModelDiscoveryService;
use LaravelDatabaseSync\Services\AutoSyncService;

class DatabaseSyncCommand extends Command
{
    protected $signature = 'db:sync {action : discover|register|status}';
    protected $description = 'Manage database synchronization';

    private $discoveryService;
    private $autoSyncService;

    public function __construct(
        ModelDiscoveryService $discoveryService,
        AutoSyncService $autoSyncService
    ) {
        parent::__construct();
        $this->discoveryService = $discoveryService;
        $this->autoSyncService = $autoSyncService;
    }

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'discover':
                $this->discoverModels();
                break;
            case 'register':
                $this->registerModels();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->error('Invalid action. Use: discover, register, or status');
        }
    }

    private function discoverModels()
    {
        $this->info('Discovering Eloquent models...');

        $models = $this->discoveryService->discoverModels();

        $this->table(['Model Class', 'Table'], array_map(function ($model) {
            try {
                $instance = new $model;
                return [$model, $instance->getTable()];
            } catch (\Exception $e) {
                return [$model, 'Error: ' . $e->getMessage()];
            }
        }, $models));

        $this->info('Found ' . count($models) . ' models');
    }

    private function registerModels()
    {
        $this->info('Registering models for sync...');

        $this->autoSyncService->registerAllModels();

        $registered = $this->autoSyncService->getRegisteredModels();
        $this->info('Registered ' . count($registered) . ' models for sync');
    }

    private function showStatus()
    {
        $this->info('Database Sync Status:');
        $this->line('Enabled: ' . (config('database-sync.enabled') ? 'Yes' : 'No'));
        $this->line('Endpoint: ' . config('database-sync.endpoint'));

        $registered = $this->autoSyncService->getRegisteredModels();
        $this->line('Registered Models: ' . count($registered));

        if (!empty($registered)) {
            $this->table(['Model'], array_map(function ($model) {
                return [$model];
            }, $registered));
        }
    }
}
