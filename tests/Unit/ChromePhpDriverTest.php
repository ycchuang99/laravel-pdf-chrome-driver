<?php

declare(strict_types=1);

use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\PdfOptions;
use Ycchuang99\LaravelPdfChromeDriver\ChromePhpDriver;

beforeEach(function () {
    $this->driver = new class extends ChromePhpDriver
    {
        /**
         * Expose protected buildCdpOptions for testing.
         *
         * @return array<string, mixed>
         */
        public function exposeBuildCdpOptions(?string $headerHtml, ?string $footerHtml, PdfOptions $options): array
        {
            return $this->buildCdpOptions($headerHtml, $footerHtml, $options);
        }

        /**
         * Expose protected toInches for testing.
         */
        public function exposeToInches(float $value, string $unit): float
        {
            return $this->toInches($value, $unit);
        }

        /**
         * Expose protected getFormatDimensions for testing.
         *
         * @return array{float, float}|null
         */
        public function exposeGetFormatDimensions(string $format): ?array
        {
            return $this->getFormatDimensions($format);
        }
    };
});

test('toInches converts millimeters correctly', function () {
    $result = $this->driver->exposeToInches(25.4, 'mm');

    expect($result)->toBeFloat()->toEqualWithDelta(1.0, 0.001);
});

test('toInches converts centimeters correctly', function () {
    $result = $this->driver->exposeToInches(2.54, 'cm');

    expect($result)->toBeFloat()->toEqualWithDelta(1.0, 0.001);
});

test('toInches passes through inches', function () {
    $result = $this->driver->exposeToInches(1.0, 'in');

    expect($result)->toEqualWithDelta(1.0, 0.001);
});

test('toInches converts pixels correctly', function () {
    $result = $this->driver->exposeToInches(96.0, 'px');

    expect($result)->toBeFloat()->toEqualWithDelta(1.0, 0.01);
});

test('getFormatDimensions returns correct A4 dimensions', function () {
    $dimensions = $this->driver->exposeGetFormatDimensions('a4');

    expect($dimensions)->toBe([8.27, 11.69]);
});

test('getFormatDimensions is case-insensitive', function () {
    $dimensions = $this->driver->exposeGetFormatDimensions('A4');

    expect($dimensions)->toBe([8.27, 11.69]);
});

test('getFormatDimensions returns correct Letter dimensions', function () {
    $dimensions = $this->driver->exposeGetFormatDimensions('letter');

    expect($dimensions)->toBe([8.5, 11.0]);
});

test('getFormatDimensions returns null for unknown format', function () {
    $dimensions = $this->driver->exposeGetFormatDimensions('unknown');

    expect($dimensions)->toBeNull();
});

test('buildCdpOptions always includes printBackground', function () {
    $options = new PdfOptions;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->toHaveKey('printBackground', true);
});

test('buildCdpOptions sets paper dimensions for named format', function () {
    $options = new PdfOptions;
    $options->format = 'a4';

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)
        ->toHaveKey('paperWidth', 8.27)
        ->toHaveKey('paperHeight', 11.69);
});

test('buildCdpOptions sets custom paper size with unit conversion', function () {
    $options = new PdfOptions;
    $options->paperSize = [
        'width' => 210.0,
        'height' => 297.0,
        'unit' => 'mm',
    ];

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result['paperWidth'])->toBeFloat()->toEqualWithDelta(8.27, 0.01);
    expect($result['paperHeight'])->toBeFloat()->toEqualWithDelta(11.69, 0.01);
});

test('buildCdpOptions sets margins with unit conversion', function () {
    $options = new PdfOptions;
    $options->margins = [
        'top' => 25.4,
        'right' => 25.4,
        'bottom' => 25.4,
        'left' => 25.4,
        'unit' => 'mm',
    ];

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result['marginTop'])->toEqualWithDelta(1.0, 0.001);
    expect($result['marginRight'])->toEqualWithDelta(1.0, 0.001);
    expect($result['marginBottom'])->toEqualWithDelta(1.0, 0.001);
    expect($result['marginLeft'])->toEqualWithDelta(1.0, 0.001);
});

test('buildCdpOptions sets landscape orientation', function () {
    $options = new PdfOptions;
    $options->orientation = Orientation::Landscape->value;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->toHaveKey('landscape', true);
});

test('buildCdpOptions does not set landscape for portrait', function () {
    $options = new PdfOptions;
    $options->orientation = Orientation::Portrait->value;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->not->toHaveKey('landscape');
});

test('buildCdpOptions sets scale', function () {
    $options = new PdfOptions;
    $options->scale = 0.75;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->toHaveKey('scale', 0.75);
});

test('buildCdpOptions sets page ranges', function () {
    $options = new PdfOptions;
    $options->pageRanges = '1-3, 5';

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->toHaveKey('pageRanges', '1-3, 5');
});

test('buildCdpOptions sets tagged PDF', function () {
    $options = new PdfOptions;
    $options->tagged = true;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->toHaveKey('generateTaggedPDF', true);
});

test('buildCdpOptions does not set tagged when false', function () {
    $options = new PdfOptions;
    $options->tagged = false;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->not->toHaveKey('generateTaggedPDF');
});

test('buildCdpOptions configures header and footer templates', function () {
    $options = new PdfOptions;
    $headerHtml = '<div>Header</div>';
    $footerHtml = '<div>Footer</div>';

    $result = $this->driver->exposeBuildCdpOptions($headerHtml, $footerHtml, $options);

    expect($result)
        ->toHaveKey('displayHeaderFooter', true)
        ->toHaveKey('headerTemplate', '<div>Header</div>')
        ->toHaveKey('footerTemplate', '<div>Footer</div>');
});

test('buildCdpOptions hides header when only footer provided', function () {
    $options = new PdfOptions;

    $result = $this->driver->exposeBuildCdpOptions(null, '<div>Footer</div>', $options);

    expect($result)
        ->toHaveKey('displayHeaderFooter', true)
        ->toHaveKey('headerTemplate', '<span></span>')
        ->toHaveKey('footerTemplate', '<div>Footer</div>');
});

test('buildCdpOptions hides footer when only header provided', function () {
    $options = new PdfOptions;

    $result = $this->driver->exposeBuildCdpOptions('<div>Header</div>', null, $options);

    expect($result)
        ->toHaveKey('displayHeaderFooter', true)
        ->toHaveKey('headerTemplate', '<div>Header</div>')
        ->toHaveKey('footerTemplate', '<span></span>');
});

test('buildCdpOptions does not set header/footer when neither provided', function () {
    $options = new PdfOptions;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)
        ->not->toHaveKey('displayHeaderFooter')
        ->not->toHaveKey('headerTemplate')
        ->not->toHaveKey('footerTemplate');
});

test('buildCdpOptions handles all options combined', function () {
    $options = new PdfOptions;
    $options->format = 'a4';
    $options->orientation = Orientation::Landscape->value;
    $options->margins = [
        'top' => 10.0,
        'right' => 10.0,
        'bottom' => 10.0,
        'left' => 10.0,
        'unit' => 'mm',
    ];
    $options->scale = 0.9;
    $options->pageRanges = '1-5';
    $options->tagged = true;

    $result = $this->driver->exposeBuildCdpOptions(
        '<div>Header</div>',
        '<div>Footer</div>',
        $options,
    );

    expect($result)
        ->toHaveKey('printBackground', true)
        ->toHaveKey('paperWidth', 8.27)
        ->toHaveKey('paperHeight', 11.69)
        ->toHaveKey('landscape', true)
        ->toHaveKey('scale', 0.9)
        ->toHaveKey('pageRanges', '1-5')
        ->toHaveKey('generateTaggedPDF', true)
        ->toHaveKey('displayHeaderFooter', true)
        ->toHaveKey('headerTemplate', '<div>Header</div>')
        ->toHaveKey('footerTemplate', '<div>Footer</div>');

    expect($result['marginTop'])->toEqualWithDelta(0.3937, 0.001);
});

test('buildCdpOptions paper size overrides named format', function () {
    $options = new PdfOptions;
    $options->format = 'a4';
    $options->paperSize = [
        'width' => 100.0,
        'height' => 200.0,
        'unit' => 'mm',
    ];

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    // paperSize should override format dimensions
    expect($result['paperWidth'])->toEqualWithDelta(3.937, 0.01);
    expect($result['paperHeight'])->toEqualWithDelta(7.874, 0.01);
});

test('driver can be constructed with config', function () {
    $driver = new ChromePhpDriver([
        'chrome_path' => '/usr/bin/google-chrome',
        'no_sandbox' => true,
        'timeout' => 60000,
    ]);

    expect($driver)->toBeInstanceOf(ChromePhpDriver::class);
});

test('driver implements PdfDriver interface', function () {
    $driver = new ChromePhpDriver;

    expect($driver)->toBeInstanceOf(PdfDriver::class);
});

test('all paper formats have valid dimensions', function () {
    $formats = ['letter', 'legal', 'tabloid', 'ledger', 'a0', 'a1', 'a2', 'a3', 'a4', 'a5', 'a6'];

    foreach ($formats as $format) {
        $dimensions = $this->driver->exposeGetFormatDimensions($format);

        expect($dimensions)
            ->toBeArray()
            ->toHaveCount(2);

        expect($dimensions[0])->toBeGreaterThan(0);
        expect($dimensions[1])->toBeGreaterThan(0);
    }
});
