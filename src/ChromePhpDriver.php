<?php

declare(strict_types=1);

namespace Ycchuang99\LaravelPdfChromeDriver;

use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\PdfOptions;

class ChromePhpDriver implements PdfDriver
{
    /**
     * Paper format dimensions in inches (width x height).
     *
     * @var array<string, array{float, float}>
     */
    protected const FORMAT_DIMENSIONS = [
        'letter' => [8.5, 11.0],
        'legal' => [8.5, 14.0],
        'tabloid' => [11.0, 17.0],
        'ledger' => [17.0, 11.0],
        'a0' => [33.1, 46.8],
        'a1' => [23.39, 33.1],
        'a2' => [16.54, 23.39],
        'a3' => [11.69, 16.54],
        'a4' => [8.27, 11.69],
        'a5' => [5.83, 8.27],
        'a6' => [4.13, 5.83],
    ];

    /**
     * Conversion factors to inches.
     *
     * @var array<string, float>
     */
    protected const UNIT_TO_INCHES = [
        'in' => 1.0,
        'mm' => 0.0393701,
        'cm' => 0.393701,
        'px' => 0.0104167,
    ];

    /** @var array<string, mixed> */
    protected array $config;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function generatePdf(string $html, ?string $headerHtml, ?string $footerHtml, PdfOptions $options): string
    {
        $browser = $this->createBrowser();

        try {
            $page = $browser->createPage();

            $page->setHtml($html, (int) ($this->config['timeout'] ?? 30000));

            $cdpOptions = $this->buildCdpOptions($headerHtml, $footerHtml, $options);
            $pdf = $page->pdf($cdpOptions);

            return $pdf->getRawBinary();
        } finally {
            $browser->close();
        }
    }

    public function savePdf(string $html, ?string $headerHtml, ?string $footerHtml, PdfOptions $options, string $path): void
    {
        $browser = $this->createBrowser();

        try {
            $page = $browser->createPage();

            $page->setHtml($html, (int) ($this->config['timeout'] ?? 30000));

            $cdpOptions = $this->buildCdpOptions($headerHtml, $footerHtml, $options);
            $pdf = $page->pdf($cdpOptions);

            $pdf->saveToFile($path, (int) ($this->config['timeout'] ?? 30000));
        } finally {
            $browser->close();
        }
    }

    protected function createBrowserFactory(): BrowserFactory
    {
        return new BrowserFactory($this->config['chrome_path'] ?? null);
    }

    protected function createBrowser(): ProcessAwareBrowser
    {
        return $this->createBrowserFactory()
            ->createBrowser($this->buildBrowserOptions());
    }

    /**
     * Build the options array for BrowserFactory::createBrowser().
     *
     * @return array<string, mixed>
     */
    protected function buildBrowserOptions(): array
    {
        $browserOptions = [
            'headless' => true,
            'noSandbox' => $this->config['no_sandbox'] ?? false,
        ];

        if (isset($this->config['window_size'])) {
            $browserOptions['windowSize'] = $this->config['window_size'];
        }

        if (isset($this->config['custom_flags'])) {
            $browserOptions['customFlags'] = $this->config['custom_flags'];
        }

        if (isset($this->config['user_data_dir'])) {
            $browserOptions['userDataDir'] = $this->config['user_data_dir'];
        }

        if (isset($this->config['startup_timeout'])) {
            $browserOptions['startupTimeout'] = $this->config['startup_timeout'];
        }

        if (isset($this->config['env_variables'])) {
            $browserOptions['envVariables'] = $this->config['env_variables'];
        }

        if (isset($this->config['ignore_certificate_errors']) && $this->config['ignore_certificate_errors']) {
            $browserOptions['ignoreCertificateErrors'] = true;
        }

        if (isset($this->config['excluded_switches'])) {
            $browserOptions['excludedSwitches'] = $this->config['excluded_switches'];
        }

        return $browserOptions;
    }

    /**
     * Build Chrome DevTools Protocol options for Page.printToPDF.
     *
     * @return array<string, mixed>
     */
    protected function buildCdpOptions(?string $headerHtml, ?string $footerHtml, PdfOptions $options): array
    {
        $cdpOptions = [
            'printBackground' => true,
        ];

        if ($options->format) {
            $dimensions = $this->getFormatDimensions($options->format);

            if ($dimensions !== null) {
                $cdpOptions['paperWidth'] = $dimensions[0];
                $cdpOptions['paperHeight'] = $dimensions[1];
            }
        }

        if ($options->paperSize) {
            $unit = $options->paperSize['unit'] ?? 'mm';
            $cdpOptions['paperWidth'] = $this->toInches((float) $options->paperSize['width'], $unit);
            $cdpOptions['paperHeight'] = $this->toInches((float) $options->paperSize['height'], $unit);
        }

        if ($options->margins) {
            $unit = $options->margins['unit'] ?? 'mm';
            $cdpOptions['marginTop'] = $this->toInches((float) $options->margins['top'], $unit);
            $cdpOptions['marginRight'] = $this->toInches((float) $options->margins['right'], $unit);
            $cdpOptions['marginBottom'] = $this->toInches((float) $options->margins['bottom'], $unit);
            $cdpOptions['marginLeft'] = $this->toInches((float) $options->margins['left'], $unit);
        }

        if ($options->orientation === Orientation::Landscape->value) {
            $cdpOptions['landscape'] = true;
        }

        if ($options->scale !== null) {
            $cdpOptions['scale'] = $options->scale;
        }

        if ($options->pageRanges !== null) {
            $cdpOptions['pageRanges'] = $options->pageRanges;
        }

        if ($options->tagged) {
            $cdpOptions['generateTaggedPDF'] = true;
        }

        if ($headerHtml || $footerHtml) {
            $cdpOptions['displayHeaderFooter'] = true;

            if ($headerHtml) {
                $cdpOptions['headerTemplate'] = $headerHtml;
            } else {
                $cdpOptions['headerTemplate'] = '<span></span>';
            }

            if ($footerHtml) {
                $cdpOptions['footerTemplate'] = $footerHtml;
            } else {
                $cdpOptions['footerTemplate'] = '<span></span>';
            }
        }

        return $cdpOptions;
    }

    /**
     * Get paper dimensions in inches for a named format.
     *
     * @return array{float, float}|null
     */
    protected function getFormatDimensions(string $format): ?array
    {
        $normalized = strtolower($format);

        return self::FORMAT_DIMENSIONS[$normalized] ?? null;
    }

    /**
     * Convert a value from the given unit to inches.
     */
    protected function toInches(float $value, string $unit): float
    {
        $factor = self::UNIT_TO_INCHES[strtolower($unit)] ?? 1.0;

        return $value * $factor;
    }
}
