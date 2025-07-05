# Laravel Database Sync

A lightweight Laravel package for automatic database synchronization via HTTP requests. This package automatically detects database changes in your Eloquent models and sends them to an external synchronization service.

## Features

- 🔄 **Automatic Sync**: Automatically syncs database changes via HTTP requests
- 🚀 **Model Discovery**: Automatically discovers and registers Eloquent models
- 📊 **Event-Driven**: Uses Laravel model events for real-time synchronization
- 🛡️ **Data Sanitization**: Automatically removes sensitive fields before syncing
- ⚙️ **Configurable**: Easy configuration for endpoints, timeouts, and filters
- 🔧 **Artisan Commands**: CLI tools for model discovery and status checking
- 📝 **Comprehensive Logging**: Detailed logging for debugging and monitoring

## Installation

### Via Composer

```bash
composer require obalaweb/laravel-database-sync
```

### Manual Installation

1. Clone this repository into your project:
```bash
git clone https://github.com/obalaweb/laravel-database-sync.git packages/laravel-database-sync
```

2. Add the package to your `composer.json`:
```json
{
    "require": {
        "obalaweb/laravel-database-sync": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "packages/laravel-database-sync"
        }
    ]
}
```

3. Run composer update:
```bash
composer update
```

## Configuration

### Publishing Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="LaravelDatabaseSync\DatabaseSyncServiceProvider" --tag="config"
```

This will create `config/database-sync.php` with all available options.

### Environment Variables

Add these to your `.env` file:

```env
# Enable/disable database sync
DATABASE_SYNC_ENABLED=true

# Sync service endpoint
DATABASE_SYNC_ENDPOINT=http://localhost:8080/sync-record

# Request timeout in seconds
DATABASE_SYNC_TIMEOUT=5
```

### Configuration Options

```php
// config/database-sync.php
return [
    // Enable or disable database sync
    'enabled' => env('DATABASE_SYNC_ENABLED', false),

    // HTTP endpoint for sync service
    'endpoint' => env('DATABASE_SYNC_ENDPOINT', 'http://localhost:8080/sync-record'),

    // Request timeout in seconds
    'timeout' => env('DATABASE_SYNC_TIMEOUT', 5),

    // Directories to scan for models
    'model_paths' => [
        app_path('Models'),
        app_path(),
    ],

    // Explicitly define models to sync (optional)
    'models' => [
        // App\Models\User::class,
        // App\Models\Post::class,
    ],

    // Only sync these tables (empty = all tables)
    'tables' => [
        // 'users',
        // 'posts',
    ],

    // Skip these tables
    'skip_tables' => [
        'migrations',
        'password_resets',
        'failed_jobs',
        'sync_queue',
        'sessions',
        'cache',
        'jobs',
    ],

    // Skip these fields from sync data
    'skip_fields' => [
        'password',
        'remember_token',
        'api_token',
        'email_verified_at',
    ],
];
```

## Usage

### Automatic Setup

Once installed and configured, the package will automatically:

1. Discover all Eloquent models in your application
2. Register event listeners for `created`, `updated`, and `deleted` events
3. Send HTTP requests to your sync service when database changes occur

### Making Models Syncable (Optional)

For advanced control, you can implement the `SyncableModelInterface`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelDatabaseSync\Contracts\SyncableModelInterface;
use LaravelDatabaseSync\Traits\SyncableModel;

class User extends Model implements SyncableModelInterface
{
    use SyncableModel;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Get data that should be synchronized
     */
    public function getSyncableData(): array
    {
        $data = $this->toArray();

        // Customize what data gets synced
        unset($data['password']);
        unset($data['remember_token']);

        return $data;
    }

    /**
     * Determine if this model should be synchronized
     */
    public function shouldSync(): bool
    {
        // Only sync active users
        return $this->status === 'active';
    }
}
```

### Disabling Sync for Specific Models

You can disable sync for specific models by adding a `$disableSync` property:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalLog extends Model
{
    // Disable sync for this model
    protected $disableSync = true;

    protected $fillable = [
        'message',
        'level',
    ];
}
```

### Artisan Commands

#### Discover Models

```bash
# Discover all Eloquent models in your application
php artisan db:sync discover
```

#### Register Models for Sync

```bash
# Register all discovered models for sync
php artisan db:sync register
```

#### Check Sync Status

```bash
# Check sync configuration and registered models
php artisan db:sync status
```

### Programmatic Usage

```php
<?php

use LaravelDatabaseSync\Services\DatabaseSyncService;
use LaravelDatabaseSync\Services\AutoSyncService;

class SyncController extends Controller
{
    public function __construct(
        private DatabaseSyncService $syncService,
        private AutoSyncService $autoSyncService
    ) {}

    public function registerModels()
    {
        $this->autoSyncService->registerAllModels();
        
        $registered = $this->autoSyncService->getRegisteredModels();
        
        return response()->json([
            'message' => 'Models registered for sync',
            'count' => count($registered),
            'models' => $registered,
        ]);
    }

    public function manualSync()
    {
        // Manually sync a record
        $success = $this->syncService->recordInsert('users', [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        return response()->json([
            'success' => $success,
        ]);
    }
}
```

## Sync Service Integration

The package sends HTTP POST requests to your sync service with the following structure:

### Request Format

```json
{
    "table_name": "users",
    "operation": "INSERT|UPDATE|DELETE",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-01-15T10:30:00.000000Z",
        "updated_at": "2025-01-15T10:30:00.000000Z"
    }
}
```

### Operations

- **INSERT**: Sent when a new record is created
- **UPDATE**: Sent when an existing record is updated
- **DELETE**: Sent when a record is deleted (only includes the ID)

### Example Sync Service (Node.js)

```javascript
const express = require('express');
const app = express();

app.use(express.json());

app.post('/sync-record', (req, res) => {
    const { table_name, operation, data } = req.body;
    
    console.log(`Sync: ${operation} on ${table_name}`, data);
    
    // Process the sync data
    // Store in external database, send to API, etc.
    
    res.json({ success: true });
});

app.listen(8080, () => {
    console.log('Sync service running on port 8080');
});
```

## Advanced Configuration

### Custom Model Discovery

```php
// config/database-sync.php
'model_paths' => [
    app_path('Models'),
    app_path('Domain/Models'),
    app_path('App/Models'),
],
```

### Selective Table Sync

```php
// config/database-sync.php
'tables' => [
    'users',
    'posts',
    'comments',
],
```

### Custom Field Filtering

```php
// config/database-sync.php
'skip_fields' => [
    'password',
    'remember_token',
    'api_token',
    'email_verified_at',
    'deleted_at',
],
```

## Logging

The package logs all sync operations. Check your Laravel logs for:

- **Debug**: Successful sync requests
- **Warning**: Failed HTTP requests
- **Error**: Exceptions and connection errors

```bash
# View sync logs
tail -f storage/logs/laravel.log | grep "Sync"
```

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage
```

## Troubleshooting

### Sync Not Working

1. Check if sync is enabled:
   ```bash
   php artisan db:sync status
   ```

2. Verify your endpoint is accessible:
   ```bash
   curl -X POST http://localhost:8080/sync-record
   ```

3. Check Laravel logs for errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Models Not Being Discovered

1. Run model discovery:
   ```bash
   php artisan db:sync discover
   ```

2. Check your model paths configuration
3. Ensure models extend `Illuminate\Database\Eloquent\Model`

### Performance Issues

1. Increase timeout in configuration
2. Consider using queue workers for sync requests
3. Monitor memory usage during large sync operations

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

If you encounter any issues or have questions, please:

1. Check the [documentation](https://github.com/obalaweb/laravel-database-sync/wiki)
2. Search [existing issues](https://github.com/obalaweb/laravel-database-sync/issues)
3. Create a [new issue](https://github.com/obalaweb/laravel-database-sync/issues/new)

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.
