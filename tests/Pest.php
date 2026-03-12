<?php

use Orchestra\Testbench\TestCase;
use Spatie\LaravelPdf\PdfServiceProvider;
use Ycchuang99\LaravelPdfChromeDriver\ChromePhpDriverServiceProvider;

uses(TestCase::class)
    ->beforeEach(function () {
        $this->app->register(PdfServiceProvider::class);
        $this->app->register(ChromePhpDriverServiceProvider::class);
    })
    ->in('Feature');
