<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom([
            database_path('migrations/admin'),
            database_path('migrations/client'),
        ]);

        // Share the configurable panel display name with every view.
        // Falls back to 'XPanel' if the table doesn't exist yet (e.g. first migrate run).
        View::composer('*', function ($view) {
            static $appName = null;
            if ($appName === null) {
                try {
                    $appName = \App\Models\SystemSetting::get('app_name', 'XPanel');
                } catch (\Throwable) {
                    $appName = 'XPanel';
                }
            }
            $view->with('appName', $appName);
        });
    }
}
