<?php

/**
 * Global helper functions for URL resolution in Blade views.
 */

if (! function_exists('base_url_for_request')) {
    /**
     * Returns the effective base URL based on the current request.
     * Falls back to config('app.url') for CLI/queue contexts.
     */
    function base_url_for_request(): string
    {
        return \App\Support\BaseUrlResolver::forRequest();
    }
}

if (! function_exists('scan_url_for_code')) {
    /**
     * Returns the full scan URL for a QR-code route code/alias.
     */
    function scan_url_for_code(string $code): string
    {
        return \App\Support\BaseUrlResolver::scanUrl($code);
    }
}
