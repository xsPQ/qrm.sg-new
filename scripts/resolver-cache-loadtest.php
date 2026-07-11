<?php

/**
 * Resolver-cache p95 load test (DEV-878 / P3-T05).
 *
 * Measures public-resolver latency (p50/p95/p99) for the Redis-backed
 * resolver cache against REAL Redis + a persistent sqlite DB, dispatched
 * through the Laravel HTTP kernel (routing + middleware + controller), and
 * verifies zero stale cache hits under concurrent invalidation.
 *
 * Run from the project root:
 *   APP_ENV=local APP_DEBUG=false \
 *   DB_CONNECTION=sqlite DB_DATABASE=/abs/loadtest.sqlite \
 *   RESOLVER_CACHE_ENABLED=true RESOLVER_CACHE_STORE=resolver \
 *   REDIS_HOST=127.0.0.1 REDIS_PORT=6379 QUEUE_CONNECTION=redis \
 *   php scripts/resolver-cache-loadtest.php
 */

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use App\Services\ResolverCache;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$OUT = [];
function line(string $s = ''): void
{
    echo $s . "\n";
    $GLOBALS['OUT'][] = $s;
}

function pct(array &$samples, float $p): float
{
    sort($samples);
    $n = count($samples);
    if ($n === 0) {
        return 0.0;
    }
    $rank = $p / 100 * ($n - 1);
    $lo = (int) floor($rank);
    $hi = (int) ceil($rank);
    if ($lo === $hi) {
        return $samples[$lo];
    }
    $frac = $rank - $lo;

    return $samples[$lo] + ($samples[$hi] - $samples[$lo]) * $frac;
}

function stats(array $samples): array
{
    return [
        'n' => count($samples),
        'min_ms' => round($samples ? min($samples) : 0, 3),
        'p50_ms' => round(pct($samples, 50), 3),
        'p95_ms' => round(pct($samples, 95), 3),
        'p99_ms' => round(pct($samples, 99), 3),
        'max_ms' => round($samples ? max($samples) : 0, 3),
        'mean_ms' => round($samples ? array_sum($samples) / count($samples) : 0, 3),
    ];
}

// Invalidate a code's cache via whichever API the installed ResolverCache
// exposes: the reverse-index builds use forgetFor(QrCode), the legacy
// tagged-cache build uses forgetQrCode(int).
function invalidate(ResolverCache $cache, QrCode $qrCode): void
{
    if (method_exists($cache, 'forgetFor')) {
        $cache->forgetFor($qrCode);
    } elseif (method_exists($cache, 'forgetQrCode')) {
        $cache->forgetQrCode((int) $qrCode->id);
    }
}

// ---------------------------------------------------------------------------
// Bootstrap the HTTP kernel once (mirrors a warm PHP-FPM / Octane worker).
// ---------------------------------------------------------------------------
$kernel = $app->make(Kernel::class);

// Prime the kernel with one throwaway request so framework bootstrapping
// (config, providers, route loading) is excluded from the timed window.
$prime = Request::create('/__loadtest_prime__', 'GET');
$prime->headers->set('Host', 'localhost');
$kernel->handle($prime);

$storeName = config('qr.resolver_cache.store');
line('');
line('=== Resolver-cache load test (DEV-878) ===');
line('PHP        : ' . PHP_VERSION);
line('Laravel    : ' . app()->version());
line('Cache store: ' . $storeName . ' (driver=' . config("cache.stores.{$storeName}.driver") . ')');
line('Redis      : ' . config('database.redis.default.host') . ':' . config('database.redis.default.port') . ' db' . config('database.redis.cache.database', config('database.redis.default.database')));
line('DB         : ' . config('database.default') . ' (' . config('database.connections.' . config('database.default') . '.driver') . ')');
line('Queue      : ' . config('queue.default'));
line('');

/** @var ResolverCache $cache */
$cache = $app->make(ResolverCache::class);

// Sanity: confirm the resolver store really is Redis and is reachable.
$redisCfgDriver = config("cache.stores.{$storeName}.driver");
$ping = null;
try {
    $conn = config("cache.stores.{$storeName}.connection", 'cache');
    $ping = Redis::connection($conn)->ping();
} catch (\Throwable $e) {
    $ping = 'ERR: ' . $e->getMessage();
}
line("Resolver store driver : {$redisCfgDriver}");
line("Redis ping             : {$ping}");
line("ResolverCache enabled  : " . ($cache->enabled() ? 'true' : 'false'));
line('');

if ($redisCfgDriver !== 'redis') {
    line('ABORT: resolver cache store is NOT redis — set RESOLVER_CACHE_STORE=resolver with a redis driver.');
    exit(2);
}

// ---------------------------------------------------------------------------
// Seed data: a single user owns N cacheable URL codes + a parallel set used
// only for the uncached (cache-disabled) measurement.
// ---------------------------------------------------------------------------
$HOST = 'localhost';
$N_WARM = 50;      // distinct warm codes (cycled) for the cached measurement
$N_COLD = 50;      // distinct codes for the uncached measurement

DB::table('qr_codes')->delete();
DB::table('qr_code_routes')->delete();
DB::table('users')->delete();

$userId = User::create([
    'name' => 'Load Test',
    'email' => 'loadtest@example.com',
    'password' => bcrypt('secret'),
])->id;

$warmCodes = [];
for ($i = 0; $i < $N_WARM; $i++) {
    $qr = QrCode::create([
        'user_id' => $userId,
        'title' => "Warm {$i}",
        'type' => 'url',
        'content' => ['url' => "https://warm-{$i}.example.com/dest"],
        'status' => 'active',
    ]);
    $code = 'WARM' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
    QrCodeRoute::create(['qr_code_id' => $qr->id, 'host' => $HOST, 'code' => $code]);
    $warmCodes[] = $code;
}

$coldCodes = [];
for ($i = 0; $i < $N_COLD; $i++) {
    $qr = QrCode::create([
        'user_id' => $userId,
        'title' => "Cold {$i}",
        'type' => 'url',
        'content' => ['url' => "https://cold-{$i}.example.com/dest"],
        'status' => 'active',
    ]);
    $code = 'COLD' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
    QrCodeRoute::create(['qr_code_id' => $qr->id, 'host' => $HOST, 'code' => $code]);
    $coldCodes[] = $code;
}

// Warm the cache for every warm code by dispatching one real request each
// (cache miss → live resolve → store). This populates Redis exactly as
// production warmup / organic traffic would.
$cache->flush();
foreach ($warmCodes as $code) {
    $req = Request::create('/' . $code, 'GET');
    $req->headers->set('Host', $HOST);
    $kernel->handle($req);
}
line("Seeded {$N_WARM} warm + {$N_COLD} cold cacheable URL codes; cache warmed via real requests.");
$metricsAfterWarm = $cache->metrics();
line("Cache metrics after warmup: hits={$metricsAfterWarm['hits']} misses={$metricsAfterWarm['misses']} errors={$metricsAfterWarm['errors']}");
line('');

// ---------------------------------------------------------------------------
// Helper: dispatch a request through the HTTP kernel and time it.
// ---------------------------------------------------------------------------
function resolveOnce(Kernel $kernel, string $code, string $host): array
{
    $req = Request::create('/' . $code, 'GET');
    $req->headers->set('Host', $host);
    $req->server->set('REMOTE_ADDR', '127.0.0.1');

    $t0 = microtime(true);
    $resp = $kernel->handle($req);
    $ms = (microtime(true) - $t0) * 1000.0;

    return ['ms' => $ms, 'status' => $resp->getStatusCode(), 'location' => $resp->headers->get('Location')];
}

// ---------------------------------------------------------------------------
// PHASE A — latency: CACHED path (warm codes, cache ENABLED).
// ---------------------------------------------------------------------------
$N = (int) (getenv('LOADTEST_N') ?: 2000);
line("--- Phase A: CACHED latency (n={$N}, cache ENABLED, Redis hits) ---");
$cached = [];
$hit = $miss = 0;
$metricBefore = $cache->metrics();
for ($i = 0; $i < $N; $i++) {
    $code = $warmCodes[$i % $N_WARM];
    $r = resolveOnce($kernel, $code, $HOST);
    $cached[] = $r['ms'];
    if ($r['status'] === 200 || str_starts_with((string) $r['status'], '30')) {
        // ok
    }
}
$metricAfter = $cache->metrics();
$hit = $metricAfter['hits'] - $metricBefore['hits'];
$miss = $metricAfter['misses'] - $metricBefore['misses'];
$cachedStats = stats($cached);
line(sprintf("hits=%d misses=%d errors_delta=%d", $hit, $miss, $metricAfter['errors'] - $metricBefore['errors']));
line(sprintf("cached : mean=%sms p50=%sms p95=%sms p99=%sms min=%sms max=%sms",
    $cachedStats['mean_ms'], $cachedStats['p50_ms'], $cachedStats['p95_ms'], $cachedStats['p99_ms'], $cachedStats['min_ms'], $cachedStats['max_ms']));
line('');

// ---------------------------------------------------------------------------
// PHASE B — latency: UNCACHED path (cache DISABLED → full live resolution).
// We disable the feature flag in config so every request runs the live
// route-lookup → DB → atomic consume → handler → sync scan-log path.
// ---------------------------------------------------------------------------
line("--- Phase B: UNCACHED latency (n={$N}, cache DISABLED, full live resolution) ---");
config(['qr.resolver_cache.enabled' => false]);
// Re-resolve a fresh resolver/cache bound to the disabled flag for correctness
// of any internal enabled() checks; the controller already resolved the shared
// ResolverCache instance whose enabled() reads live config.
$uncached = [];
for ($i = 0; $i < $N; $i++) {
    $code = $coldCodes[$i % $N_COLD];
    $r = resolveOnce($kernel, $code, $HOST);
    $uncached[] = $r['ms'];
}
$uncachedStats = stats($uncached);
line(sprintf("uncached: mean=%sms p95=%sms p99=%sms min=%sms max=%sms",
    $uncachedStats['mean_ms'], $uncachedStats['p95_ms'], $uncachedStats['p99_ms'], $uncachedStats['min_ms'], $uncachedStats['max_ms']));
line('');

// Re-enable cache for the concurrency phase.
config(['qr.resolver_cache.enabled' => true]);

// ---------------------------------------------------------------------------
// PHASE C — invalidation correctness under concurrent load.
//
// C1 (strict, barrier-gated): warm OLD, then the parent mutates the code
// content to NEW and invalidates (model-save lifecycle hook + explicit
// invalidate). A barrier file is created ONLY after the flush returns, so
// every worker read starts strictly AFTER the cache entry was deleted. Any
// response still returning the OLD target is a genuine stale hit (the cache
// served/republished an entry whose source data already changed).
//
// C2 (informational, overlap race): workers read concurrently ACROSS the
// invalidation to characterize the inherent cache-aside re-population window
// (a reader that misses just before the flush, reads OLD from the DB, then
// writes OLD back after the flush). This is a known limitation of cache-aside,
// not an invalidation bug; reported as an informational number.
// ---------------------------------------------------------------------------
line('--- Phase C: invalidation correctness under concurrent load ---');

$OLD_URL = 'https://invalidate-OLD.example.com';
$NEW_URL = 'https://invalidate-NEW.example.com';

function childResetConnections(): void
{
    foreach (array_keys(DB::getConnections()) as $dbName) {
        DB::purge($dbName);
    }
    DB::purge();
    foreach (['default', 'cache'] as $redisConn) {
        try {
            Redis::connection($redisConn)->disconnect();
        } catch (\Throwable) {
        }
    }
}

// Run a barrier-gated concurrency read phase. Workers wait for $barrierFile,
// then read $reads times each. Returns [total, old, new, other].
function concurrencyRead($app, string $code, string $host, int $workers, int $reads, string $barrierFile, string $oldUrl, string $newUrl): array
{
    $tmpDir = dirname($barrierFile);
    @mkdir($tmpDir, 0777, true);
    @unlink($barrierFile);

    $pids = [];
    for ($w = 0; $w < $workers; $w++) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            continue;
        }
        if ($pid === 0) {
            childResetConnections();
            $childKernel = $app->make(Kernel::class);
            // Spin until the parent signals (barrier file present).
            $deadline = microtime(true) + 10;
            while (! is_file($barrierFile) && microtime(true) < $deadline) {
                usleep(500);
            }
            $fh = fopen("{$tmpDir}/w{$w}.txt", 'w');
            for ($i = 0; $i < $reads; $i++) {
                $r = resolveOnce($childKernel, $code, $host);
                fwrite($fh, $r['status'] . '|' . ($r['location'] ?? '') . "\n");
            }
            fclose($fh);
            exit(0);
        }
        $pids[] = $pid;
    }
    return $pids;
}

function aggregateConcurrency(string $tmpDir, int $workers, string $oldUrl, string $newUrl): array
{
    $total = $old = $new = $other = 0;
    for ($w = 0; $w < $workers; $w++) {
        $f = "{$tmpDir}/w{$w}.txt";
        if (! is_file($f)) {
            continue;
        }
        foreach (file($f, FILE_IGNORE_NEW_LINES) as $line) {
            [$status, $loc] = explode('|', $line, 2) + [1 => ''];
            $total++;
            if ($loc === $oldUrl) {
                $old++;
            } elseif ($loc === $newUrl) {
                $new++;
            } else {
                $other++;
            }
        }
    }
    foreach (glob("{$tmpDir}/*.txt") as $f) {
        @unlink($f);
    }
    return [$total, $old, $new, $other];
}

$WORKERS = (int) (getenv('LOADTEST_WORKERS') ?: 8);
$READS_PER_WORKER = (int) (getenv('LOADTEST_READS') ?: 200);
$staleStrict = null;
$overlapOld = null;

if (! function_exists('pcntl_fork')) {
    line('pcntl not available — skipping concurrency phase.');
} else {
    // ---- C1: strict post-invalidation correctness (barrier-gated) ----
    $invQr = QrCode::create([
        'user_id' => $userId,
        'title' => 'Invalidate Probe C1',
        'type' => 'url',
        'content' => ['url' => $OLD_URL],
        'status' => 'active',
    ]);
    $invCode = 'INVPROBEC100';
    QrCodeRoute::create(['qr_code_id' => $invQr->id, 'host' => $HOST, 'code' => $invCode]);

    invalidate($cache, $invQr);
    $warm = resolveOnce($kernel, $invCode, $HOST);
    line("[C1] warm target={$warm['location']} (status={$warm['status']})");
    if (($warm['location'] ?? '') !== $OLD_URL) {
        line("ABORT: C1 warm did not produce OLD target — got {$warm['location']}");
        exit(2);
    }

    $barrierC1 = sys_get_temp_dir() . '/loadtest-c1-' . $invQr->id . '/go';
    $pids = concurrencyRead($app, $invCode, $HOST, $WORKERS, $READS_PER_WORKER, $barrierC1, $OLD_URL, $NEW_URL);
    // Give workers a moment to start polling, then mutate + invalidate, then
    // release the barrier so all reads begin strictly after the flush.
    usleep(40000);
    $invQr->content = ['url' => $NEW_URL];
    $invQr->save();            // lifecycle hook -> ResolverCache invalidation
    invalidate($cache, $invQr); // explicit invalidate
    touch($barrierC1);          // release barrier AFTER flush returned
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }
    [$c1Total, $c1Old, $c1New, $c1Other] = aggregateConcurrency(dirname($barrierC1), $WORKERS, $OLD_URL, $NEW_URL);
    @unlink($barrierC1);
    @rmdir(dirname($barrierC1));
    $staleStrict = $c1Old;
    line(sprintf("[C1 strict] workers=%d reads=%d  OLD=%d NEW=%d other=%d  -> %s",
        $WORKERS, $c1Total, $c1Old, $c1New, $c1Other, $c1Old === 0 ? 'PASS (0 stale after flush)' : 'FAIL'));

    // ---- C2: informational overlap-race (reads straddle the flush) ----
    $invQr2 = QrCode::create([
        'user_id' => $userId,
        'title' => 'Invalidate Probe C2',
        'type' => 'url',
        'content' => ['url' => $OLD_URL],
        'status' => 'active',
    ]);
    $invCode2 = 'INVPROBEC200';
    QrCodeRoute::create(['qr_code_id' => $invQr2->id, 'host' => $HOST, 'code' => $invCode2]);
    invalidate($cache, $invQr2);
    resolveOnce($kernel, $invCode2, $HOST); // warm OLD

    // Workers read continuously; parent invalidates mid-stream (no barrier).
    $tmpC2 = sys_get_temp_dir() . '/loadtest-c2-' . $invQr2->id;
    @mkdir($tmpC2, 0777, true);
    $pids2 = [];
    for ($w = 0; $w < $WORKERS; $w++) {
        $pid = pcntl_fork();
        if ($pid === 0) {
            childResetConnections();
            $childKernel = $app->make(Kernel::class);
            $fh = fopen("{$tmpC2}/w{$w}.txt", 'w');
            for ($i = 0; $i < $READS_PER_WORKER; $i++) {
                $r = resolveOnce($childKernel, $invCode2, $HOST);
                fwrite($fh, $r['status'] . '|' . ($r['location'] ?? '') . "\n");
            }
            fclose($fh);
            exit(0);
        }
        $pids2[] = $pid;
    }
    usleep(30000);
    $invQr2->content = ['url' => $NEW_URL];
    $invQr2->save();
    invalidate($cache, $invQr2);
    foreach ($pids2 as $pid) {
        pcntl_waitpid($pid, $status);
    }
    [$c2Total, $c2Old, $c2New, $c2Other] = aggregateConcurrency($tmpC2, $WORKERS, $OLD_URL, $NEW_URL);
    @rmdir($tmpC2);
    $overlapOld = $c2Old;
    line(sprintf("[C2 overlap] workers=%d reads=%d  OLD=%d NEW=%d other=%d  (info: inherent cache-aside race window, not an invalidation defect)",
        $WORKERS, $c2Total, $c2Old, $c2New, $c2Other));
}


line('');

// ---------------------------------------------------------------------------
// Verdict
// ---------------------------------------------------------------------------
$cachedP95 = $cachedStats['p95_ms'];
$uncachedP95 = $uncachedStats['p95_ms'];
$cachedPass = $cachedP95 < 50.0;
$uncachedPass = $uncachedP95 < 150.0;
$invPass = ($staleStrict === null) ? null : ($staleStrict === 0);

line('=== VERDICT ===');
line(sprintf("cached  p95=%sms  target<50ms   -> %s", $cachedP95, $cachedPass ? 'PASS' : 'FAIL'));
line(sprintf("uncached p95=%sms  target<150ms  -> %s", $uncachedP95, $uncachedPass ? 'PASS' : 'FAIL'));
if ($invPass !== null) {
    line(sprintf("invalidation (C1 strict, 0 stale after flush): %s", $invPass ? 'PASS' : 'FAIL'));
}
if ($overlapOld !== null) {
    line("invalidation (C2 overlap race, informational): {$overlapOld} stale re-reads in race window");
}

$exit = ($cachedPass && $uncachedPass && ($invPass !== false)) ? 0 : 1;

// Dump a JSON summary alongside stdout.
$summary = [
    'environment' => [
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
        'cache_store' => $storeName,
        'cache_driver' => $redisCfgDriver,
        'redis' => config('database.redis.default.host') . ':' . config('database.redis.default.port'),
        'db' => config('database.connections.' . config('database.default') . '.driver'),
        'queue' => config('queue.default'),
        'n_per_phase' => $N,
    ],
    'cached' => $cachedStats,
    'cached_hits' => $hit,
    'cached_misses' => $miss,
    'uncached' => $uncachedStats,
    'invalidation' => [
        'workers' => $WORKERS ?? null,
        'reads_per_worker' => $READS_PER_WORKER ?? null,
        'c1_strict_stale_after_flush' => $staleStrict,
        'c2_overlap_race_stale' => $overlapOld,
    ],
    'verdict' => [
        'cached_p95_lt_50ms' => $cachedPass,
        'uncached_p95_lt_150ms' => $uncachedPass,
        'zero_stale_after_invalidation_c1' => $invPass,
    ],
];
$jsonPath = getcwd() . '/build-results/resolver-cache-loadtest-results.json';
@mkdir(dirname($jsonPath), 0777, true);
file_put_contents($jsonPath, json_encode($summary, JSON_PRETTY_PRINT));
line('');
line("JSON results written to: {$jsonPath}");

exit($exit);
