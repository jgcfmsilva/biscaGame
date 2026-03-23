<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\AddingCoinsAtRegister\UserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ligamos o Observer ao Modelo User.
        // Agora, sempre que um User for criado, o método 'created' do UserObserver será executado.
        User::observe(UserObserver::class);
    }
}