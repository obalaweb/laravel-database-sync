<?php

namespace Obalaweb\LaravelDatabaseSync\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class ModelDiscoveryService
{
    /**
     * Discover all Eloquent models in the application
     */
    public function discoverModels(): array
    {
        $models = [];

        // Get model directories from config
        $modelPaths = config('database-sync.model_paths', [
            app_path('Models'),
            app_path(), // For older Laravel versions
        ]);

        foreach ($modelPaths as $path) {
            if (File::isDirectory($path)) {
                $models = array_merge($models, $this->scanDirectory($path));
            }
        }

        // Also check for explicitly defined models
        $explicitModels = config('database-sync.models', []);
        foreach ($explicitModels as $model) {
            if (class_exists($model)) {
                $models[] = $model;
            }
        }

        return array_unique($models);
    }

    /**
     * Scan directory for model files
     */
    private function scanDirectory(string $path): array
    {
        $models = [];

        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $class = $this->getClassFromFile($file->getPathname());

                if ($class && $this->isEloquentModel($class)) {
                    $models[] = $class;
                }
            }
        }

        return $models;
    }

    /**
     * Get class name from file
     */
    private function getClassFromFile(string $filePath): ?string
    {
        $content = File::get($filePath);

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        } else {
            $namespace = '';
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
            return $namespace ? $namespace . '\\' . $className : $className;
        }

        return null;
    }

    /**
     * Check if class is an Eloquent model
     */
    private function isEloquentModel(string $class): bool
    {
        try {
            if (!class_exists($class)) {
                return false;
            }

            $reflection = new ReflectionClass($class);

            // Skip abstract classes
            if ($reflection->isAbstract()) {
                return false;
            }

            // Check if it extends Model
            return $reflection->isSubclassOf(Model::class);
        } catch (\Exception $e) {
            return false;
        }
    }
}
