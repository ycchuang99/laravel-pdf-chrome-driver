<?php

declare(strict_types=1);

use Spatie\LaravelPdf\Drivers\PdfDriver;
use Ycchuang99\LaravelPdfChromePhpDriver\ChromePhpDriver;

test('service provider registers chrome-php driver singleton', function () {
    expect($this->app->bound('laravel-pdf.driver.chrome-php'))->toBeTrue();
});

test('chrome-php driver singleton returns ChromePhpDriver instance', function () {
    $driver = $this->app->make('laravel-pdf.driver.chrome-php');

    expect($driver)->toBeInstanceOf(ChromePhpDriver::class);
});

test('driver is resolved as default when config driver is chrome-php', function () {
    config(['laravel-pdf.driver' => 'chrome-php']);

    // Clear the existing singleton so it re-resolves
    $this->app->forgetInstance(PdfDriver::class);

    $driver = $this->app->make(PdfDriver::class);

    expect($driver)->toBeInstanceOf(ChromePhpDriver::class);
});

test('driver receives config from laravel-pdf.chrome-php', function () {
    config(['laravel-pdf.chrome-php.no_sandbox' => true]);
    config(['laravel-pdf.chrome-php.timeout' => 60000]);

    // Clear existing singleton so it re-resolves with new config
    $this->app->forgetInstance('laravel-pdf.driver.chrome-php');

    $driver = $this->app->make('laravel-pdf.driver.chrome-php');

    expect($driver)->toBeInstanceOf(ChromePhpDriver::class);
});
