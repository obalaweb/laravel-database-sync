<?php

namespace LaravelDatabaseSync\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DatabaseSyncService
{
    private $endpoint;
    private $timeout;
    private $enabled;

    public function __construct()
    {
        $this->endpoint = config('database-sync.endpoint', 'http://localhost:8080/sync-record');
        $this->timeout = config('database-sync.timeout', 5);
        $this->enabled = config('database-sync.enabled', true);
    }

    /**
     * Send sync record to the wrapper service
     */
    public function syncRecord(string $tableName, string $operation, array $data): bool
    {
        if (!$this->enabled) {
            return true;
        }

        // Skip if table is in skip list
        if (in_array($tableName, config('database-sync.skip_tables', []))) {
            return true;
        }

        // Filter to only sync specified tables if configured
        $syncTables = config('database-sync.tables', []);
        if (!empty($syncTables) && !in_array($tableName, $syncTables)) {
            return true;
        }

        try {
            $response = Http::timeout($this->timeout)->post($this->endpoint, [
                'table_name' => $tableName,
                'operation' => $operation,
                'data' => $this->sanitizeData($data)
            ]);

            if ($response->successful()) {
                Log::debug("Sync record sent: {$tableName} - {$operation}");
                return true;
            } else {
                Log::warning("Sync failed: {$tableName} - {$operation} - HTTP {$response->status()}");
                return false;
            }
        } catch (Exception $e) {
            Log::error("Sync error: {$tableName} - {$operation} - {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Sanitize data before sending
     */
    private function sanitizeData(array $data): array
    {
        $skipFields = config('database-sync.skip_fields', [
            'password',
            'remember_token',
            'api_token'
        ]);

        return array_diff_key($data, array_flip($skipFields));
    }

    /**
     * Record insert operation
     */
    public function recordInsert(string $tableName, array $data): bool
    {
        return $this->syncRecord($tableName, 'INSERT', $data);
    }

    /**
     * Record update operation
     */
    public function recordUpdate(string $tableName, array $data): bool
    {
        return $this->syncRecord($tableName, 'UPDATE', $data);
    }

    /**
     * Record delete operation
     */
    public function recordDelete(string $tableName, array $data): bool
    {
        return $this->syncRecord($tableName, 'DELETE', $data);
    }
}
