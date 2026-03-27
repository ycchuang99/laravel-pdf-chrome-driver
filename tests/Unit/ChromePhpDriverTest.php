<?php

declare(strict_types=1);

use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use HeadlessChromium\PageUtils\PagePdf;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\PdfOptions;
use Ycchuang99\LaravelPdfChromeDriver\ChromePhpDriver;

/**
 * Returns a ChromePhpDriver subclass that exposes protected helpers for testing.
 *
 * @param  array<string, mixed>  $config
 */
function makeTestableDriver(array $config = []): ChromePhpDriver
{
    return new class($config) extends ChromePhpDriver
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
         * Expose protected buildBrowserOptions for testing.
         *
         * @return array<string, mixed>
         */
        public function exposeBuildBrowserOptions(): array
        {
            return $this->buildBrowserOptions();
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

        /**
         * Expose protected createBrowserFactory for testing.
         */
        public function exposeCreateBrowserFactory(): BrowserFactory
        {
            return $this->createBrowserFactory();
        }
    };
}

/**
 * Returns a ChromePhpDriver subclass whose createBrowser() is replaced by a
 * callback so tests can inject a mock browser without real Chrome.
 *
 * @param  array<string, mixed>  $config
 */
function makeDriverWithBrowser(ProcessAwareBrowser $mockBrowser, array $config = []): ChromePhpDriver
{
    return new class($mockBrowser, $config) extends ChromePhpDriver
    {
        public function __construct(
            private readonly ProcessAwareBrowser $mockBrowser,
            array $config = [],
        ) {
            parent::__construct($config);
        }

        protected function createBrowser(): ProcessAwareBrowser
        {
            return $this->mockBrowser;
        }
    };
}

/**
 * Returns a ChromePhpDriver subclass whose createBrowserFactory() is replaced
 * with the given mock, so tests can exercise createBrowser() without Chrome.
 *
 * @param  array<string, mixed>  $config
 */
function makeDriverWithMockFactory(BrowserFactory $mockFactory, array $config = []): ChromePhpDriver
{
    return new class($mockFactory, $config) extends ChromePhpDriver
    {
        public function __construct(
            private readonly BrowserFactory $mockFactory,
            array $config = [],
        ) {
            parent::__construct($config);
        }

        protected function createBrowserFactory(): BrowserFactory
        {
            return $this->mockFactory;
        }

        public function exposeCreateBrowser(): ProcessAwareBrowser
        {
            return $this->createBrowser();
        }
    };
}

beforeEach(function () {
    $this->driver = makeTestableDriver();
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

test('toInches falls back to factor 1.0 for unknown unit', function () {
    $result = $this->driver->exposeToInches(5.0, 'unknown');

    expect($result)->toEqualWithDelta(5.0, 0.001);
});

test('toInches unit matching is case-insensitive', function () {
    $lower = $this->driver->exposeToInches(25.4, 'mm');
    $upper = $this->driver->exposeToInches(25.4, 'MM');

    expect($lower)->toEqualWithDelta($upper, 0.0001);
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

test('buildBrowserOptions returns headless true and noSandbox false by default', function () {
    $options = $this->driver->exposeBuildBrowserOptions();

    expect($options)
        ->toHaveKey('headless', true)
        ->toHaveKey('noSandbox', false);
});

test('buildBrowserOptions respects no_sandbox config', function () {
    $driver = makeTestableDriver(['no_sandbox' => true]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('noSandbox', true);
});

test('buildBrowserOptions includes windowSize when window_size is set', function () {
    $driver = makeTestableDriver(['window_size' => [1920, 1080]]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('windowSize', [1920, 1080]);
});

test('buildBrowserOptions omits windowSize when window_size is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('windowSize');
});

test('buildBrowserOptions includes customFlags when custom_flags is set', function () {
    $flags = ['--disable-gpu', '--disable-dev-shm-usage'];
    $driver = makeTestableDriver(['custom_flags' => $flags]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('customFlags', $flags);
});

test('buildBrowserOptions omits customFlags when custom_flags is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('customFlags');
});

test('buildBrowserOptions includes userDataDir when user_data_dir is set', function () {
    $driver = makeTestableDriver(['user_data_dir' => '/tmp/chrome-user-data']);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('userDataDir', '/tmp/chrome-user-data');
});

test('buildBrowserOptions omits userDataDir when user_data_dir is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('userDataDir');
});

test('buildBrowserOptions includes startupTimeout when startup_timeout is set', function () {
    $driver = makeTestableDriver(['startup_timeout' => 60]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('startupTimeout', 60);
});

test('buildBrowserOptions omits startupTimeout when startup_timeout is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('startupTimeout');
});

test('buildBrowserOptions includes envVariables when env_variables is set', function () {
    $driver = makeTestableDriver(['env_variables' => ['DISPLAY' => ':0']]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('envVariables', ['DISPLAY' => ':0']);
});

test('buildBrowserOptions omits envVariables when env_variables is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('envVariables');
});

test('buildBrowserOptions includes ignoreCertificateErrors when flag is true', function () {
    $driver = makeTestableDriver(['ignore_certificate_errors' => true]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('ignoreCertificateErrors', true);
});

test('buildBrowserOptions omits ignoreCertificateErrors when flag is false', function () {
    $driver = makeTestableDriver(['ignore_certificate_errors' => false]);

    expect($driver->exposeBuildBrowserOptions())->not->toHaveKey('ignoreCertificateErrors');
});

test('buildBrowserOptions omits ignoreCertificateErrors when flag is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('ignoreCertificateErrors');
});

test('buildBrowserOptions includes excludedSwitches when excluded_switches is set', function () {
    $driver = makeTestableDriver(['excluded_switches' => ['--enable-automation']]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('excludedSwitches', ['--enable-automation']);
});

test('buildBrowserOptions omits excludedSwitches when excluded_switches is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('excludedSwitches');
});

test('buildBrowserOptions includes sendSyncDefaultTimeout when send_sync_default_timeout is set', function () {
    $driver = makeTestableDriver(['send_sync_default_timeout' => 10000]);

    expect($driver->exposeBuildBrowserOptions())->toHaveKey('sendSyncDefaultTimeout', 10000);
});

test('buildBrowserOptions omits sendSyncDefaultTimeout when send_sync_default_timeout is not set', function () {
    expect($this->driver->exposeBuildBrowserOptions())->not->toHaveKey('sendSyncDefaultTimeout');
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

test('buildCdpOptions paper size defaults unit to mm when unit key is absent', function () {
    $options = new PdfOptions;
    $options->paperSize = [
        'width' => 210.0,
        'height' => 297.0,
        // no 'unit' key — should default to 'mm'
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

test('buildCdpOptions margins defaults unit to mm when unit key is absent', function () {
    $options = new PdfOptions;
    $options->margins = [
        'top' => 25.4,
        'right' => 25.4,
        'bottom' => 25.4,
        'left' => 25.4,
        // no 'unit' key — should default to 'mm'
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

test('buildCdpOptions does not set scale when null', function () {
    $options = new PdfOptions;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->not->toHaveKey('scale');
});

test('buildCdpOptions sets page ranges', function () {
    $options = new PdfOptions;
    $options->pageRanges = '1-3, 5';

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->toHaveKey('pageRanges', '1-3, 5');
});

test('buildCdpOptions does not set pageRanges when null', function () {
    $options = new PdfOptions;

    $result = $this->driver->exposeBuildCdpOptions(null, null, $options);

    expect($result)->not->toHaveKey('pageRanges');
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

test('generatePdf returns binary string from browser', function () {
    $mockPdf = $this->createMock(PagePdf::class);
    $mockPdf->method('getRawBinary')->willReturn('%PDF-1.4 binary content');

    $mockPage = $this->createMock(Page::class);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser);

    $result = $driver->generatePdf('<h1>Hello</h1>', null, null, new PdfOptions);

    expect($result)->toBe('%PDF-1.4 binary content');
});

test('generatePdf passes html to setHtml with default timeout', function () {
    $mockPdf = $this->createMock(PagePdf::class);
    $mockPdf->method('getRawBinary')->willReturn('');

    $mockPage = $this->createMock(Page::class);
    $mockPage->expects($this->once())
        ->method('setHtml')
        ->with('<p>content</p>', 30000);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser);
    $driver->generatePdf('<p>content</p>', null, null, new PdfOptions);
});

test('generatePdf passes html to setHtml with custom timeout from config', function () {
    $mockPdf = $this->createMock(PagePdf::class);
    $mockPdf->method('getRawBinary')->willReturn('');

    $mockPage = $this->createMock(Page::class);
    $mockPage->expects($this->once())
        ->method('setHtml')
        ->with('<p>test</p>', 60000);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser, ['timeout' => 60000]);
    $driver->generatePdf('<p>test</p>', null, null, new PdfOptions);
});

test('generatePdf passes cdp options to page pdf', function () {
    $mockPdf = $this->createMock(PagePdf::class);
    $mockPdf->method('getRawBinary')->willReturn('');

    $mockPage = $this->createMock(Page::class);
    $mockPage->expects($this->once())
        ->method('pdf')
        ->with($this->arrayHasKey('printBackground'))
        ->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser);
    $driver->generatePdf('<h1>Test</h1>', null, null, new PdfOptions);
});

test('generatePdf closes browser on success', function () {
    $mockPdf = $this->createMock(PagePdf::class);
    $mockPdf->method('getRawBinary')->willReturn('');

    $mockPage = $this->createMock(Page::class);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);
    $mockBrowser->expects($this->once())->method('close');

    $driver = makeDriverWithBrowser($mockBrowser);
    $driver->generatePdf('<h1>Test</h1>', null, null, new PdfOptions);
});

test('generatePdf closes browser even when exception is thrown', function () {
    $mockPage = $this->createMock(Page::class);
    $mockPage->method('setHtml')->willThrowException(new RuntimeException('Chrome crashed'));

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);
    $mockBrowser->expects($this->once())->method('close');

    $driver = makeDriverWithBrowser($mockBrowser);

    expect(fn () => $driver->generatePdf('<h1>Test</h1>', null, null, new PdfOptions))
        ->toThrow(RuntimeException::class, 'Chrome crashed');
});

test('savePdf saves to the given file path', function () {
    $mockPdf = $this->createMock(PagePdf::class);
    $mockPdf->expects($this->once())
        ->method('saveToFile')
        ->with('/tmp/output.pdf', 30000);

    $mockPage = $this->createMock(Page::class);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser);
    $driver->savePdf('<h1>Test</h1>', null, null, new PdfOptions, '/tmp/output.pdf');
});

test('savePdf passes html to setHtml with default timeout', function () {
    $mockPdf = $this->createMock(PagePdf::class);

    $mockPage = $this->createMock(Page::class);
    $mockPage->expects($this->once())
        ->method('setHtml')
        ->with('<p>save me</p>', 30000);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser);
    $driver->savePdf('<p>save me</p>', null, null, new PdfOptions, '/tmp/out.pdf');
});

test('savePdf uses custom timeout from config for saveToFile', function () {
    $mockPdf = $this->createMock(PagePdf::class);
    $mockPdf->expects($this->once())
        ->method('saveToFile')
        ->with('/tmp/output.pdf', 90000);

    $mockPage = $this->createMock(Page::class);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser, ['timeout' => 90000]);
    $driver->savePdf('<h1>Test</h1>', null, null, new PdfOptions, '/tmp/output.pdf');
});

test('savePdf closes browser on success', function () {
    $mockPdf = $this->createMock(PagePdf::class);

    $mockPage = $this->createMock(Page::class);
    $mockPage->method('pdf')->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);
    $mockBrowser->expects($this->once())->method('close');

    $driver = makeDriverWithBrowser($mockBrowser);
    $driver->savePdf('<h1>Test</h1>', null, null, new PdfOptions, '/tmp/out.pdf');
});

test('savePdf closes browser even when exception is thrown', function () {
    $mockPage = $this->createMock(Page::class);
    $mockPage->method('setHtml')->willThrowException(new RuntimeException('Save crashed'));

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);
    $mockBrowser->expects($this->once())->method('close');

    $driver = makeDriverWithBrowser($mockBrowser);

    expect(fn () => $driver->savePdf('<h1>Test</h1>', null, null, new PdfOptions, '/tmp/out.pdf'))
        ->toThrow(RuntimeException::class, 'Save crashed');
});

test('savePdf passes cdp options to page pdf', function () {
    $options = new PdfOptions;
    $options->format = 'a4';

    $mockPdf = $this->createMock(PagePdf::class);

    $mockPage = $this->createMock(Page::class);
    $mockPage->expects($this->once())
        ->method('pdf')
        ->with($this->arrayHasKey('paperWidth'))
        ->willReturn($mockPdf);

    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockBrowser->method('createPage')->willReturn($mockPage);

    $driver = makeDriverWithBrowser($mockBrowser);
    $driver->savePdf('<h1>Test</h1>', null, null, $options, '/tmp/out.pdf');
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

// ─── createBrowserFactory ────────────────────────────────────────────────────

test('createBrowserFactory returns a BrowserFactory instance', function () {
    $factory = $this->driver->exposeCreateBrowserFactory();

    expect($factory)->toBeInstanceOf(BrowserFactory::class);
});

test('createBrowserFactory uses chrome_path from config', function () {
    $driver = makeTestableDriver(['chrome_path' => '/usr/bin/google-chrome']);
    $factory = $driver->exposeCreateBrowserFactory();

    $ref = new ReflectionClass($factory);
    $prop = $ref->getProperty('chromeBinary');

    expect($prop->getValue($factory))->toBe('/usr/bin/google-chrome');
});

test('createBrowserFactory uses CHROME_PATH env variable when chrome_path config is not set', function () {
    $previous = $_SERVER['CHROME_PATH'] ?? null;
    $_SERVER['CHROME_PATH'] = '/opt/custom/chrome';

    try {
        $factory = $this->driver->exposeCreateBrowserFactory();

        $ref = new ReflectionClass($factory);
        $prop = $ref->getProperty('chromeBinary');

        expect($prop->getValue($factory))->toBe('/opt/custom/chrome');
    } finally {
        if ($previous !== null) {
            $_SERVER['CHROME_PATH'] = $previous;
        } else {
            unset($_SERVER['CHROME_PATH']);
        }
    }
});

// ─── createBrowser ───────────────────────────────────────────────────────────

test('createBrowser calls factory createBrowser with the built browser options', function () {
    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockFactory = $this->createMock(BrowserFactory::class);
    $mockFactory->expects($this->once())
        ->method('createBrowser')
        ->with($this->arrayHasKey('headless'))
        ->willReturn($mockBrowser);

    $driver = makeDriverWithMockFactory($mockFactory);
    $result = $driver->exposeCreateBrowser();

    expect($result)->toBe($mockBrowser);
});

test('createBrowser passes headless true to factory', function () {
    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockFactory = $this->createMock(BrowserFactory::class);
    $mockFactory->expects($this->once())
        ->method('createBrowser')
        ->with($this->callback(fn (array $opts) => $opts['headless'] === true))
        ->willReturn($mockBrowser);

    makeDriverWithMockFactory($mockFactory)->exposeCreateBrowser();
});

test('createBrowser passes noSandbox from config to factory', function () {
    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockFactory = $this->createMock(BrowserFactory::class);
    $mockFactory->expects($this->once())
        ->method('createBrowser')
        ->with($this->callback(fn (array $opts) => $opts['noSandbox'] === true))
        ->willReturn($mockBrowser);

    makeDriverWithMockFactory($mockFactory, ['no_sandbox' => true])->exposeCreateBrowser();
});

test('createBrowser passes windowSize from config to factory', function () {
    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockFactory = $this->createMock(BrowserFactory::class);
    $mockFactory->expects($this->once())
        ->method('createBrowser')
        ->with($this->callback(fn (array $opts) => $opts['windowSize'] === [1920, 1080]))
        ->willReturn($mockBrowser);

    makeDriverWithMockFactory($mockFactory, ['window_size' => [1920, 1080]])->exposeCreateBrowser();
});

test('createBrowser returns the browser instance from factory', function () {
    $mockBrowser = $this->createMock(ProcessAwareBrowser::class);
    $mockFactory = $this->createMock(BrowserFactory::class);
    $mockFactory->method('createBrowser')->willReturn($mockBrowser);

    $result = makeDriverWithMockFactory($mockFactory)->exposeCreateBrowser();

    expect($result)->toBe($mockBrowser);
});
