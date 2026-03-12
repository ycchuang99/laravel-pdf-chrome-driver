# Chrome PHP Driver for spatie/laravel-pdf

A [chrome-php/chrome](https://github.com/chrome-php/chrome) driver for [spatie/laravel-pdf](https://github.com/spatie/laravel-pdf).

This package provides a PDF generation driver that uses `chrome-php/chrome` (a PHP library for headless Chrome) instead of Browsershot. No Node.js required — pure PHP communication with Chrome via the DevTools Protocol.

## Requirements

- PHP 8.2+
- Chrome/Chromium binary installed on the system
- `spatie/laravel-pdf` ^2.0

## Version Compatibility

| Package Version | PHP   | spatie/laravel-pdf | chrome-php/chrome |
|-----------------|-------|--------------------|-------------------|
| 1.x             | ^8.2  | ^2.0               | ^1.0              |

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

Or publish the config file:

```bash
php artisan vendor:publish --tag=chrome-php-config
```

### Available Configuration Options

Add to your `config/laravel-pdf.php`:

```php
'chrome-php' => [
    'chrome_path' => env('CHROME_PHP_BINARY'),
    'no_sandbox' => env('CHROME_PHP_NO_SANDBOX', false),
    'timeout' => env('CHROME_PHP_TIMEOUT', 30000),
    'startup_timeout' => env('CHROME_PHP_STARTUP_TIMEOUT', 30),
    'window_size' => null,
    'custom_flags' => null,
    'user_data_dir' => null,
    'env_variables' => null,
    'ignore_certificate_errors' => env('CHROME_PHP_IGNORE_CERT_ERRORS', false),
    'excluded_switches' => null,
],
```

### Environment Variables

| Variable                        | Description                            | Default       |
|---------------------------------|----------------------------------------|---------------|
| `LARAVEL_PDF_DRIVER`            | Set to `chrome-php` to use this driver | `browsershot` |
| `CHROME_PHP_BINARY`             | Path to Chrome/Chromium binary         | Auto-detected |
| `CHROME_PHP_NO_SANDBOX`         | Disable sandbox (for Docker)           | `false`       |
| `CHROME_PHP_TIMEOUT`            | Timeout in milliseconds                | `30000`       |
| `CHROME_PHP_STARTUP_TIMEOUT`    | Chrome startup timeout in seconds      | `30`          |
| `CHROME_PHP_IGNORE_CERT_ERRORS` | Ignore SSL certificate errors          | `false`       |

## Usage

Works exactly like `spatie/laravel-pdf` — just change the driver:

```php
use Spatie\LaravelPdf\Facades\Pdf;

// Generate inline PDF
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

### Docker Usage

When running in Docker, enable no-sandbox mode:

```env
CHROME_PHP_NO_SANDBOX=true
```

You may also need custom flags:

```php
// config/laravel-pdf.php
'chrome-php' => [
    'no_sandbox' => true,
    'custom_flags' => ['--disable-gpu', '--disable-dev-shm-usage'],
],
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
