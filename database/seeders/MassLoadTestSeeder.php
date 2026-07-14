<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Entitlement\EntitlementSnapshot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MassLoadTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding 100 000 QR codes...');
        $start = microtime(true);

        $now = now()->toDateTimeString();
        $types = ['url', 'message', 'wifi', 'social', 'redirect', 'crypto', 'event', 'contact'];
        $statuses = ['active', 'active', 'active', 'active', 'expired', 'burned'];
        $hash = '$2y$12$t8yVO/TPF96bk2pyBiJr9OGbfPfk9XadUHElUONI2st/fXn24Em56'; // precomputed bcrypt for 'test1234'

        // --- 1. Users ---
        $this->command->info('Creating 1 000 users...');
        $userRows = [];
        for ($i = 1; $i <= 1000; $i++) {
            $plan = $i <= 100 ? 'business' : ($i <= 400 ? 'pro' : 'free');
            $userRows[] = [
                'name' => "User {$i}",
                'email' => "loadtest{$i}@qrm.sg",
                'password' => $hash,
                'plan' => $plan,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($userRows, 500) as $chunk) {
            DB::table('users')->insert($chunk);
        }
        $users = DB::table('users')->orderBy('id')->get(['id', 'plan']);
        $this->command->info("Users: {$users->count()}");

        // --- 2. QR Codes (100 per user, bulk per user) ---
        $this->command->info('Creating codes...');
        $total = 0;
        $usedCodes = [];

        foreach ($users as $idx => $user) {
            $snapshot = json_encode(EntitlementSnapshot::forPlan($user->plan)->toArray());
            $qrRows = [];

            for ($j = 0; $j < 100; $j++) {
                $type = $types[array_rand($types)];
                $status = $statuses[array_rand($statuses)];
                $hasPassword = random_int(1, 100) <= 10;
                $burn = random_int(1, 100) <= 5;
                $expiresAt = $status === 'expired'
                    ? now()->subDays(random_int(1, 30))->toDateTimeString()
                    : ($user->plan === 'free' ? now()->addDays(30)->toDateTimeString() : null);

                $qrRows[] = [
                    'user_id' => $user->id,
                    'title' => "Code #{$total} — " . Str::random(12),
                    'type' => $type,
                    'content' => json_encode($this->contentForType($type, $total)),
                    'settings' => json_encode(['style' => ['fg_color' => '#000000']]),
                    'status' => $status,
                    'entitlement_snapshot' => $snapshot,
                    'password_hash' => $hasPassword ? $hash : null,
                    'burn' => $burn,
                    'scan_count' => random_int(0, 5000),
                    'max_scans' => random_int(1, 100) <= 5 ? random_int(10, 1000) : null,
                    'expires_at' => $expiresAt,
                    'next_cleanup_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $total++;
            }

            DB::table('qr_codes')->insert($qrRows);

            // Routes
            $insertedIds = DB::table('qr_codes')
                ->where('user_id', $user->id)
                ->where('created_at', $now)
                ->orderByDesc('id')
                ->limit(100)
                ->pluck('id')
                ->toArray();

            $routeRows = [];
            foreach ($insertedIds as $qrId) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (isset($usedCodes[$code]));
                $usedCodes[$code] = true;
                $routeRows[] = [
                    'qr_code_id' => $qrId,
                    'host' => 'qrm.sg',
                    'code' => $code,
                    'alias' => null,
                    'created_at' => $now,
                ];
            }
            DB::table('qr_code_routes')->insert($routeRows);

            if (($idx + 1) % 50 === 0) {
                $el = round(microtime(true) - $start, 1);
                $done = $idx + 1;
                $this->command->info("  {$done}/1000 users, {$total} codes ({$el}s)");
            }
        }

        // Sync sequences
        DB::statement("SELECT setval('qr_codes_id_seq', (SELECT max(id) FROM qr_codes))");
        DB::statement("SELECT setval('users_id_seq', (SELECT max(id) FROM users))");
        DB::statement("SELECT setval('qr_code_routes_id_seq', (SELECT max(id) FROM qr_code_routes))");

        $el = round(microtime(true) - $start, 1);
        $this->command->info("Done in {$el}s!");

        $stats = [
            'codes' => DB::table('qr_codes')->count(),
            'active' => DB::table('qr_codes')->where('status', 'active')->count(),
            'expired' => DB::table('qr_codes')->where('status', 'expired')->count(),
            'burned' => DB::table('qr_codes')->where('status', 'burned')->count(),
            'password' => DB::table('qr_codes')->whereNotNull('password_hash')->count(),
            'total_scans' => DB::table('qr_codes')->sum('scan_count'),
            'routes' => DB::table('qr_code_routes')->count(),
            'users' => DB::table('users')->count(),
            'db_size' => DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database()))")->size,
        ];
        $this->command->table(['Metric', 'Value'], collect($stats)->map(fn($v, $k) => [$k, is_string($v) ? $v : number_format($v)])->values()->toArray());
    }

    /**
     * Build type-specific QR payloads for the load test seed.
     *
     * @return array<string, mixed>
     */
    private function contentForType(string $type, int $index): array
    {
        return match ($type) {
            'url' => [
                'url' => "https://example.com/load-test/{$index}",
            ],
            'message' => [
                'message' => "Load test message {$index}",
            ],
            'redirect' => [
                'url' => "https://redirect.example.com/{$index}",
            ],
            'social' => [
                'platform' => 'twitter',
                'username' => "loadtest{$index}",
            ],
            'wifi' => [
                'ssid' => "LoadTestWiFi{$index}",
                'password' => "password{$index}",
                'encryption' => 'WPA',
            ],
            'crypto' => [
                'coin' => 'bitcoin',
                'address' => "bc1qloadtest{$index}",
            ],
            'event' => [
                'title' => "Load Test Event {$index}",
                'start' => now()->addDays($index % 30 + 1)->toIso8601String(),
                'end' => now()->addDays($index % 30 + 1)->addHour()->toIso8601String(),
                'location' => 'Load Test Hall',
            ],
            'contact' => [
                'name' => "Load Test {$index}",
                'phone' => '+1-202-555-01' . str_pad((string) ($index % 100), 2, '0', STR_PAD_LEFT),
                'email' => "loadtest{$index}@example.com",
            ],
            default => throw new \InvalidArgumentException("Unsupported QR type: {$type}"),
        };
    }
}
