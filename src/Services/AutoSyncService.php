<?php

namespace Obalaweb\LaravelDatabaseSync\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Obalaweb\LaravelDatabaseSync\Contracts\SyncableModelInterface;

class AutoSyncService
{
    private $syncService;
    private $discoveryService;
    private $registeredModels = [];

    public function __construct(
        DatabaseSyncService $syncService,
        ModelDiscoveryService $discoveryService
    ) {
        $this->syncService = $syncService;
        $this->discoveryService = $discoveryService;
    }

    /**
     * Register all discovered models for sync
     */
    public function registerAllModels(): void
    {
        $models = $this->discoveryService->discoverModels();

        foreach ($models as $model) {
            $this->registerModel($model);
        }

        Log::info("Database sync registered for " . count($this->registeredModels) . " models");
    }

    /**
     * Register a specific model for sync
     */
    public function registerModel(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            Log::warning("Model class not found: {$modelClass}");
            return;
        }

        if (in_array($modelClass, $this->registeredModels)) {
            return; // Already registered
        }

        try {
            // Register event listeners for this model
            $this->registerModelEvents($modelClass);
            $this->registeredModels[] = $modelClass;

            Log::debug("Registered sync for model: {$modelClass}");
        } catch (\Exception $e) {
            Log::error("Failed to register sync for model {$modelClass}: {$e->getMessage()}");
        }
    }

    /**
     * Register event listeners for a model
     */
    private function registerModelEvents(string $modelClass): void
    {
        // Create event listeners
        $modelClass::created(function (Model $model) {
            $this->handleModelCreated($model);
        });

        $modelClass::updated(function (Model $model) {
            $this->handleModelUpdated($model);
        });

        $modelClass::deleted(function (Model $model) {
            $this->handleModelDeleted($model);
        });
    }

    /**
     * Handle model created event
     */
    private function handleModelCreated(Model $model): void
    {
        $tableName = $model->getTable();
        $data = $this->getModelData($model);

        $this->syncService->recordInsert($tableName, $data);
    }

    /**
     * Handle model updated event
     */
    private function handleModelUpdated(Model $model): void
    {
        $tableName = $model->getTable();
        $data = $this->getModelData($model);

        $this->syncService->recordUpdate($tableName, $data);
    }

    /**
     * Handle model deleted event
     */
    private function handleModelDeleted(Model $model): void
    {
        $tableName = $model->getTable();
        $data = ['id' => $model->getKey()];

        $this->syncService->recordDelete($tableName, $data);
    }

    /**
     * Get model data for sync
     */
    private function getModelData(Model $model): array
    {
        $data = $model->toArray();

        // If model implements SyncableModelInterface, use custom data
        if ($model instanceof SyncableModelInterface) {
            $data = $model->getSyncableData();
        }

        return $data;
    }

    /**
     * Get registered models
     */
    public function getRegisteredModels(): array
    {
        return $this->registeredModels;
    }
}
