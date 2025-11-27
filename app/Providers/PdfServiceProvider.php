<?php

namespace App\Providers;

use App\Services\PdfService\FakePdfService;
use App\Services\PdfService\PdfService;
use Illuminate\Support\ServiceProvider;

class PdfServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (
            app()->environment('production')
            || app()->environment('local')
            || app()->environment('staging')
        ) {
            /**
             * Register the original PdfService in production, local, and staging environments
             */
            $this->app->singleton(PdfService::class, function ($app) {
                return new PdfService;
            });
        } else {
            /**
             * Register the FakePdfService in other environments, such as testing
             * because we dont want to make any http calls to the pdf service
             */
            $this->app->singleton(PdfService::class, function ($app) {
                return new FakePdfService;
            });
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
