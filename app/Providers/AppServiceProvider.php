<?php

namespace App\Providers;

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\ServiceProvider;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        \Schema::defaultStringLength(191);
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        } else {
            \URL::forceScheme('https');
        }
        Resource::withoutWrapping();


    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('hash_auth', function ($attribute, $value, $parameters, $validator) {
            return \Hash::check(AuthController::KEY, $value);
        });
//        NotificationMessage::observe(NotificationMessageObserver::class);
    }
}
