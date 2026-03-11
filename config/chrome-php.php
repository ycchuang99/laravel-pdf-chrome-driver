<?php

return [
    /*
     * Path to the Chrome/Chromium binary.
     * Leave null to use auto-detection.
     */
    'chrome_path' => env('CHROME_PHP_BINARY'),

    /*
     * Disable the sandbox for environments like Docker.
     */
    'no_sandbox' => env('CHROME_PHP_NO_SANDBOX', false),

    /*
     * Timeout in milliseconds for page load and PDF generation.
     */
    'timeout' => env('CHROME_PHP_TIMEOUT', 30000),

    /*
     * Maximum time in seconds to wait for Chrome to start.
     */
    'startup_timeout' => env('CHROME_PHP_STARTUP_TIMEOUT', 30),

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
    'ignore_certificate_errors' => env('CHROME_PHP_IGNORE_CERT_ERRORS', false),

    /*
     * Chrome flags to remove from the default set.
     * Example: ['--enable-automation']
     */
    'excluded_switches' => null,
];
