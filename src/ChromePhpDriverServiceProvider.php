<?php

declare(strict_types=1);

namespace Ycchuang99\LaravelPdfChromeDriver;

use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Exceptions\InvalidDriver;

class ChromePhpDriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/chrome-php.php', 'laravel-pdf.chrome-php');

        $this->app->singleton('laravel-pdf.driver.chrome-php', function () {
            return new ChromePhpDriver(
                config('laravel-pdf.chrome-php', []),
            );
        });

        $this->app->singleton(PdfDriver::class, function ($app) {
            $driverName = config('laravel-pdf.driver', 'browsershot');

            if ($driverName === 'chrome-php') {
                return $app->make('laravel-pdf.driver.chrome-php');
            }

            $key = "laravel-pdf.driver.{$driverName}";

            if ($app->bound($key)) {
                return $app->make($key);
            }

            throw InvalidDriver::unknown($driverName);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/chrome-php.php' => config_path('laravel-pdf-chrome-php.php'),
            ], 'chrome-php-config');
        }
    }
}
