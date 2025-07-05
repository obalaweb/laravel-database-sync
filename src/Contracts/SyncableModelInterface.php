<?php

namespace LaravelDatabaseSync\Contracts;

interface SyncableModelInterface
{
    /**
     * Get data that should be synchronized
     */
    public function getSyncableData(): array;

    /**
     * Determine if this model should be synchronized
     */
    public function shouldSync(): bool;
}
