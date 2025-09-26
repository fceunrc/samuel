<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nada especial: tu Sanctum no soporta ignoreMigrations().
        // Evitamos el choque simplemente no teniendo la migración en Samuel.
    }

    public function boot(): void
    {
        //
    }
}
