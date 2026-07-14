<?php

/**
 * End-to-end HTTP latency benchmark for the resolver (DEV-878).
 *
 * Fires N sequential GET requests at a running resolver server and reports
 * p50/p95/p99 round-trip latency (network + full per-request framework
 * bootstrap + application logic). Run against the PHP built-in server
 * (`artisan serve`), which bootstraps the app fresh per request WITHOUT
 * opcache — so these numbers are a conservative upper bound (production
 * PHP-FPM + OPcache is faster).
 *
 * Usage: php scripts/resolver-cache-http-bench.php <url> <N> <label>
 */

$uri = $argv[1] ?? 'http://127.0.0.1:8765/WARM0000';
$n = (int) ($argv[2] ?? 500);
$label = $argv[3] ?? 'http';

$samples = [];
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $uri,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_FORBID_REUSE => false,
]);

// Warm-up the curl/TCP connection (excluded from samples).
for ($i = 0; $i < 5; $i++) {
    curl_exec($ch);
}

for ($i = 0; $i < $n; $i++) {
    curl_exec($ch);
    $info = curl_getinfo($ch);
    $samples[] = $info['total_time'] * 1000.0; // ms
}
curl_close($ch);

sort($samples);
$pick = fn (float $p) => round($samples[(int) floor($p / 100 * (count($samples) - 1))], 3);
$mean = round(array_sum($samples) / count($samples), 3);

echo "{$label}: n={$n} mean={$mean}ms p50={$pick(50)}ms p95={$pick(95)}ms p99={$pick(99)}ms min=" . round(min($samples), 3) . "ms max=" . round(max($samples), 3) . "ms\n";
echo json_encode(['label' => $label, 'n' => $n, 'mean_ms' => $mean, 'p50_ms' => $pick(50), 'p95_ms' => $pick(95), 'p99_ms' => $pick(99), 'min_ms' => round(min($samples), 3), 'max_ms' => round(max($samples), 3)]) . "\n";
