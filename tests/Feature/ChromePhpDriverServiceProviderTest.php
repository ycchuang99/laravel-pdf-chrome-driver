<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Ycchuang99\LaravelPdfChromeDriver\ChromePhpDriver;
use Ycchuang99\LaravelPdfChromeDriver\ChromePhpDriverServiceProvider;

test('service provider registers chrome-php driver singleton', function () {
    expect($this->app->bound('laravel-pdf.driver.chrome-php'))->toBeTrue();
});

test('chrome-php driver singleton returns ChromePhpDriver instance', function () {
    $driver = $this->app->make('laravel-pdf.driver.chrome-php');

    expect($driver)->toBeInstanceOf(ChromePhpDriver::class);
});

test('driver is a singleton – same instance returned on repeated resolution', function () {
    $first = $this->app->make('laravel-pdf.driver.chrome-php');
    $second = $this->app->make('laravel-pdf.driver.chrome-php');

    expect($first)->toBe($second);
});

test('driver receives config from laravel-pdf.chrome-php', function () {
    config(['laravel-pdf.chrome-php.no_sandbox' => true]);
    config(['laravel-pdf.chrome-php.timeout' => 60000]);

    $this->app->forgetInstance('laravel-pdf.driver.chrome-php');

    $driver = $this->app->make('laravel-pdf.driver.chrome-php');

    expect($driver)->toBeInstanceOf(ChromePhpDriver::class);
});

test('service provider merges default config for laravel-pdf.chrome-php', function () {
    expect(config('laravel-pdf.chrome-php'))->toBeArray();
});

test('default no_sandbox config value is false', function () {
    expect(config('laravel-pdf.chrome-php.no_sandbox'))->toBeFalse();
});

test('default timeout config value is 30000', function () {
    expect(config('laravel-pdf.chrome-php.timeout'))->toBe(30000);
});

test('default startup_timeout config value is 30', function () {
    expect(config('laravel-pdf.chrome-php.startup_timeout'))->toBe(30);
});

test('default chrome_path config value is null', function () {
    expect(config('laravel-pdf.chrome-php.chrome_path'))->toBeNull();
});

test('default window_size config value is null', function () {
    expect(config('laravel-pdf.chrome-php.window_size'))->toBeNull();
});

test('default custom_flags config value is null', function () {
    expect(config('laravel-pdf.chrome-php.custom_flags'))->toBeNull();
});

test('default user_data_dir config value is null', function () {
    expect(config('laravel-pdf.chrome-php.user_data_dir'))->toBeNull();
});

test('default env_variables config value is null', function () {
    expect(config('laravel-pdf.chrome-php.env_variables'))->toBeNull();
});

test('default ignore_certificate_errors config value is false', function () {
    expect(config('laravel-pdf.chrome-php.ignore_certificate_errors'))->toBeFalse();
});

test('default excluded_switches config value is null', function () {
    expect(config('laravel-pdf.chrome-php.excluded_switches'))->toBeNull();
});

test('service provider publishes chrome-php-config tag', function () {
    $publishes = ServiceProvider::pathsToPublish(
        ChromePhpDriverServiceProvider::class,
        'chrome-php-config',
    );

    expect($publishes)->toBeArray()->not->toBeEmpty();

    // Source path must point to an existing config file
    $sourcePath = array_key_first($publishes);
    expect($sourcePath)->toBeFile();
});
