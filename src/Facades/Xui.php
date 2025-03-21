<?php

namespace TimBogdanov\Xui\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Xui
 *
 * @method static array getAllUsers(?string $inboundId = null)
 * @method static array getUserByTgId(int $tgId, ?string $inboundId = null)
 * @method static array getTrafficByUuid(string $uuid)
 * @method static array updateUser(array $userData, ?string $inboundId = null)
 * @method static array addUser(array $userData, ?string $inboundId = null)
 * @method static array deleteUser(string $uuid, ?string $inboundId = null)
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
