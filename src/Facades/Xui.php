<?php

namespace TimBogdanov\Xui\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class XuiService
 *
 * @method static array indexClients(?string $inboundId = null) Retrieves all clients for a given inbound.
 * @method static array showClientByTgId(int $tgId, ?string $inboundId = null) Retrieves a client by their Telegram ID.
 * @method static array getClientTraffic(string $uuid) Retrieves traffic data for a client based on UUID.
 * @method static array updateClient(array $clientData, ?string $inboundId = null) Updates an existing client.
 * @method static array storeClient(array $clientData, ?string $inboundId = null) Creates a new client.
 * @method static array destroyClient(string $uuid, ?string $inboundId = null) Deletes an existing client based on UUID.
 */

class Xui extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TimBogdanov\Xui\XuiService::class;
    }
}
