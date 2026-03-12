# Chrome PHP Driver for spatie/laravel-pdf

A [chrome-php/chrome](https://github.com/chrome-php/chrome) driver for [spatie/laravel-pdf](https://github.com/spatie/laravel-pdf).

This package provides a PDF generation driver that uses `chrome-php/chrome` (a PHP library for headless Chrome) instead of Browsershot. No Node.js required — pure PHP communication with Chrome via the DevTools Protocol.

## Requirements

- PHP 8.4+
- Chrome or Chromium binary installed on the system
- `spatie/laravel-pdf` ^2.0

## Version Compatibility

| Package Version | PHP   | spatie/laravel-pdf | chrome-php/chrome |
|-----------------|-------|--------------------|-------------------|
| 1.x             | ^8.4  | ^2.0               | ^1.0              |

## Installation

```bash
composer require ycchuang99/laravel-pdf-chrome-php-driver
```

The service provider is auto-discovered by Laravel.

## Configuration

Set the driver in your `.env` file:

```env
LARAVEL_PDF_DRIVER=chrome-php
```

### Available Configuration Options

Add to your `config/laravel-pdf.php`:

```php
'chrome-php' => [
    'chrome_path'               => env('LARAVEL_PDF_CHROME_PATH'),
    'no_sandbox'                => env('LARAVEL_PDF_NO_SANDBOX', false),
    'timeout'                   => env('LARAVEL_PDF_TIMEOUT', 30000),
    'startup_timeout'           => env('LARAVEL_PDF_STARTUP_TIMEOUT', 30),
    'window_size'               => null,
    'custom_flags'              => null,
    'user_data_dir'             => null,
    'env_variables'             => null,
    'ignore_certificate_errors' => env('LARAVEL_PDF_IGNORE_CERT_ERRORS', false),
    'excluded_switches'         => null,
],
```

### Environment Variables

| Variable                        | Description                            | Default       |
|---------------------------------|----------------------------------------|---------------|
| `LARAVEL_PDF_DRIVER`            | Set to `chrome-php` to use this driver | `browsershot` |
| `LARAVEL_PDF_CHROME_PATH`       | Path to Chrome/Chromium binary         | Auto-detected |
| `LARAVEL_PDF_NO_SANDBOX`        | Disable sandbox (required in Docker)   | `false`       |
| `LARAVEL_PDF_TIMEOUT`           | Timeout in milliseconds                | `30000`       |
| `LARAVEL_PDF_STARTUP_TIMEOUT`   | Chrome startup timeout in seconds      | `30`          |
| `LARAVEL_PDF_IGNORE_CERT_ERRORS`| Ignore SSL certificate errors          | `false`       |

## Usage

Works exactly like `spatie/laravel-pdf` — just change the driver:

```php
use Spatie\LaravelPdf\Facades\Pdf;

// Generate and save a PDF
Pdf::view('invoice', ['order' => $order])
    ->format('a4')
    ->save('/path/to/invoice.pdf');

// With all options
Pdf::html('<h1>Hello World</h1>')
    ->format('a4')
    ->landscape()
    ->margins(10, 10, 10, 10, 'mm')
    ->headerHtml('<div style="font-size:10px">Header</div>')
    ->footerHtml('<div style="font-size:10px">Page <span class="pageNumber"></span></div>')
    ->scale(0.9)
    ->save('/path/to/output.pdf');

// Use driver per-call (without changing default)
Pdf::view('invoice', $data)
    ->driver('chrome-php')
    ->save('/path/to/invoice.pdf');
```

### Docker / CI Usage

When running in Docker or CI environments, enable no-sandbox mode and add the recommended Chrome flags:

```php
// config/laravel-pdf.php
'chrome-php' => [
    'no_sandbox'     => true,
    'env_variables'  => ['HOME' => '/tmp', 'XDG_CONFIG_HOME' => '/tmp/.config'],
],
```

Or via environment variables (for `no_sandbox` only):

```env
LARAVEL_PDF_NO_SANDBOX=true
```

#### Why these flags?

**`env_variables`** — Environment variables injected into the Chrome process:

| Variable | Value | Purpose |
|----------|-------|---------|
| `HOME` | `/tmp` | Chrome needs a writable home directory for crash dumps and preferences. In restricted environments the real `$HOME` may not exist or be writable. |
| `XDG_CONFIG_HOME` | `/tmp/.config` | Chrome follows the XDG Base Directory spec on Linux. Pointing it to `/tmp` prevents failures when the real config path doesn't exist. |

> **Why aren't these the defaults?** These are environment-specific workarounds. On a local machine or bare-metal server, GPU acceleration works fine and `$HOME` is set correctly. Enabling these flags unconditionally would silently degrade performance and override system-level config paths where they are not needed.

## Testing

See [CONTRIBUTING.md](CONTRIBUTING.md) for instructions on running the test suite, coverage, and other QA targets.

## License

MIT License. See [LICENSE](LICENSE) for details.
