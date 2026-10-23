<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        // Защита от N+1: в разработке и тестах ленивая загрузка связей — ошибка.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
