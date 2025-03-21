# Xui Package

A Laravel package to interact with the x‑ui panel. This package provides methods for managing x‑ui panel users—including retrieving, updating, creating, and deleting users through the x‑ui API—while also supporting multiple inbound IDs.

## Features

- **Client Management:** Retrieve all users, get user by Telegram ID, update, add, and delete users.
- **Traffic Data:** Retrieve traffic data for a user by UUID.
- **Re-Synchronization:** Easily re-sync a user’s data with the x‑ui panel.
- **Multiple Inbounds:** Optionally specify an inbound ID; defaults to the configuration value if not provided.
- **Easy Configuration:** Publish and customize the configuration file for your x‑ui panel credentials and settings.
- **Optional Facade:** Provides a simple facade for quick access.

## Installation

You can install the package via Composer:

```bash
composer require timbogdanov/xui
```
Configuration
After installing the package, publish the configuration file using Artisan:

```bash
php artisan vendor:publish --tag=config
```
This will create a config/xui.php file. Edit this file to set your x‑ui panel details:

```php
<?php

return [
    'host'      => env('XUI_HOST', 'example.com'),
    'port'      => env('XUI_PORT', '8443'),
    'path'      => env('XUI_PATH', 'api'),
    'username'  => env('XUI_USERNAME', 'admin'),
    'password'  => env('XUI_PASSWORD', 'secret'),
    'inboundId' => env('XUI_INBOUND_ID', '1'), // default inbound id
];
```
Also, add the corresponding environment variables to your .env file if needed.

## Usage
### Using Dependency Injection 
You can type-hint the service in your controller or other classes:

```php
use TimBogdanov\Xui\XuiService;

class ClientController extends Controller
{
    public function index(XuiService $xui)
    {
        $users = $xui->getAllClients();
        return response()->json($users);
    }

    public function delete($uuid, XuiService $xui)
    {
        $response = $xui->deleteClient($uuid);
        return response()->json($response);
    }
}
```

### Using the Facade
If you prefer to use the provided facade, add the alias to your configuration (this is done automatically if you use Laravel's package auto-discovery):

```php
use Xui;

$users = Xui::getAllClients();
```

### Available Methods
- **getAllClients(?string $inboundId = null): array**
Retrieves all users (clients) for a given inbound.

- **getClientByTgId(int $tgId, ?string $inboundId = null)**
Retrieves a user by their Telegram ID.

- **getTrafficByUuid(string $uuid): array**
Retrieves traffic data for a user based on UUID.

- **updateClient(array $userData, ?string $inboundId = null): array**
Updates an existing user. Expects user data with keys such as uuid, email, tgId, etc.

- **addClient(array $userData, ?string $inboundId = null): array**
Adds a new user with the given data. A random email or UUID may be generated if not provided.

- **deleteClient(string $uuid, ?string $inboundId = null): array**
Deletes an existing user based on their UUID.

- reSync($user, ?string $inboundId = null): void
Synchronizes a user’s data with the x‑ui panel. If a user exists (based on Telegram ID), it updates the record; otherwise, it creates a new one.

## Contributing
Contributions are welcome! Please open issues or submit pull requests on the GitHub repository.

## License
This package is open-sourced software licensed under the MIT license.