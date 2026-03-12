<?php

return [
    /*
     * Path to the Chrome/Chromium binary.
     * Leave null to use auto-detection.
     */
    'chrome_path' => env('LARAVEL_PDF_CHROME_PATH'),

    /*
     * Disable the sandbox for environments like Docker.
     */
    'no_sandbox' => env('LARAVEL_PDF_NO_SANDBOX', false),

    /*
     * Timeout in milliseconds for page load and PDF generation.
     */
    'timeout' => env('LARAVEL_PDF_TIMEOUT', 30000),

    /*
     * Maximum time in seconds to wait for Chrome to start.
     */
    'startup_timeout' => env('LARAVEL_PDF_STARTUP_TIMEOUT', 30),

    /*
     * Chrome window size as [width, height].
     * Leave null for default.
     */
    'window_size' => null,

    /*
     * Custom Chrome flags to pass to the command line.
     * Example: ['--disable-gpu', '--disable-dev-shm-usage']
     */
    'custom_flags' => null,

    /*
     * Chrome user data directory.
     * Leave null to use a temporary directory.
     */
    'user_data_dir' => null,

    /*
     * Environment variables to pass to the Chrome process.
     * Example: ['DISPLAY' => ':0']
     */
    'env_variables' => null,

    /*
     * Ignore SSL certificate errors.
     */
    'ignore_certificate_errors' => env('LARAVEL_PDF_IGNORE_CERT_ERRORS', false),

    /*
     * Chrome flags to remove from the default set.
     * Example: ['--enable-automation']
     */
    'excluded_switches' => null,
];
