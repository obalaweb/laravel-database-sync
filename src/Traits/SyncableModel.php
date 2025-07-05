<?php

namespace Obalaweb\LaravelDatabaseSync\Traits;

use Obalaweb\LaravelDatabaseSync\Contracts\SyncableModelInterface;

trait SyncableModel
{
    /**
     * Get data that should be synchronized
     */
    public function getSyncableData(): array
    {
        $data = $this->toArray();

        // Remove sensitive fields
        $hidden = $this->getHidden();
        foreach ($hidden as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Determine if this model should be synchronized
     */
    public function shouldSync(): bool
    {
        // Check if model has sync disabled
        if (property_exists($this, 'disableSync') && $this->disableSync) {
            return false;
        }

        return true;
    }
}
