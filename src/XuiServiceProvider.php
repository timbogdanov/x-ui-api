<?php


namespace TimBogdanov\Xui;

use Illuminate\Support\ServiceProvider;

class XuiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/xui.php' => config_path('xui.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/xui.php', 'xui');

        $this->app->singleton(XuiService::class, function ($app) {
            return new XuiService();
        });
    }
}
