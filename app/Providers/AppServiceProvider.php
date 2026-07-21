<?php

namespace App\Providers;

use App\Auth\AirtableUserProvider;
use App\Repositories\AppointmentRepository;
use App\Repositories\ConversationRepository;
use App\Repositories\PatientRepository;
use App\Repositories\UserRepository;
use App\Services\AirtableClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AirtableClient::class, function () {
            return AirtableClient::fromConfig();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('airtable', function ($app, array $config) {
            return new AirtableUserProvider($app->make(UserRepository::class));
        });

        Route::bind('patient', function (string $value) {
            return app(PatientRepository::class)->findOrFail($value);
        });

        Route::bind('appointment', function (string $value) {
            return app(AppointmentRepository::class)->findOrFail($value);
        });

        Route::bind('user', function (string $value) {
            return app(UserRepository::class)->findOrFail($value);
        });

        Route::bind('conversation', function (string $value) {
            return app(ConversationRepository::class)->findOrFail($value);
        });
    }
}
