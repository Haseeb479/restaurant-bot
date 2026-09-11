<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // ── G1: Validate Critical Environment Configuration ─────────────────
        if ($this->app->environment('production')) {
            $criticalKeys = [
                'GROQ_API_KEY'        => config('services.groq.key') ?: env('GROQ_API_KEY'),
                'EVOLUTION_API_KEY'   => config('services.evolution.api_key') ?: env('EVOLUTION_API_KEY'),
                'GOOGLE_MAPS_API_KEY' => config('services.google.maps_api_key') ?: env('GOOGLE_MAPS_API_KEY'),
            ];

            foreach ($criticalKeys as $keyName => $val) {
                if (empty($val)) {
                    \Illuminate\Support\Facades\Log::critical("CONFIGURATION ERROR: Required environment variable [{$keyName}] is missing in production!");
                }
            }
        }
    }
}
