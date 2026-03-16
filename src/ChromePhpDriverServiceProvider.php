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
            return new ChromePhpDriver(config('laravel-pdf.chrome-php', []));
        });

        $this->app->singleton(PdfDriver::class, function () {
            $driverName = config('laravel-pdf.driver', 'browsershot');

            return match ($driverName) {
                'browsershot' => app('laravel-pdf.driver.browsershot'),
                'cloudflare' => app('laravel-pdf.driver.cloudflare'),
                'dompdf' => app('laravel-pdf.driver.dompdf'),
                'gotenberg' => app('laravel-pdf.driver.gotenberg'),
                'weasyprint' => app('laravel-pdf.driver.weasyprint'),
                'chrome-php' => app('laravel-pdf.driver.chrome-php'),

                default => throw InvalidDriver::unknown($driverName),
            };
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
