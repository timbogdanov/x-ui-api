<?php

namespace TimBogdanov\Xui\Facades;

use Illuminate\Support\Facades\Facade;

class Xui extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TimBogdanov\Xui\XuiService::class;
    }
}
