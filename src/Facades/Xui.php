<?php

namespace TimBogdanov\Xui\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Xui
 *
 * @method static array getAllClients(?string $inboundId = null)
 * @method static array getClientByTgId(int $tgId, ?string $inboundId = null)
 * @method static array getTrafficByUuid(string $uuid)
 * @method static array updateClient(array $userData, ?string $inboundId = null)
 * @method static array addClient(array $userData, ?string $inboundId = null)
 * @method static array deleteClient(string $uuid, ?string $inboundId = null)
 * @method static void reSync($user, ?string $inboundId = null)
 *
 * @see \TimBogdanov\Xui\XuiService
 */
class Xui extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TimBogdanov\Xui\XuiService::class;
    }
}
