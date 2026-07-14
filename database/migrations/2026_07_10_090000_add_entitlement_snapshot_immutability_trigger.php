<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S-Tier hardening (DEV-592): enforce the entitlement_snapshot immutability
 * invariant (Pflichtenheft §3.5.3, §6.2) at the database level, in addition to
 * the application-level `saving` guard on the QrCode model.
 *
 * The snapshot is set exactly once at QR-code creation and must never be
 * mutated afterwards — not by edits, and not by a billing webhook reacting to
 * a plan downgrade (Bestandsschutz / grandfathering). The model guard protects
 * the Eloquent write path; this trigger also blocks every path that bypasses
 * the model: raw `DB::table`/query-builder updates, `forceFill()`,
 * `saveQuietly()`, replication events and any future webhook code that writes
 * directly to the table.
 *
 * Semantics intentionally mirror the model guard:
 *   - INSERT is never affected (trigger fires on UPDATE only).
 *   - First-time assignment (NULL -> value) on an existing row is allowed.
 *   - Re-persisting the structurally identical value is allowed (so ordinary
 *     saves of unrelated columns never trip the trigger).
 *   - Any change from a non-null value to a different value is rejected.
 *
 * Portable across SQLite (used by the test suite) and PostgreSQL (production),
 * because the migration framework runs on both drivers.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_qr_codes_entitlement_snapshot_mutation()
                RETURNS trigger
                LANGUAGE plpgsql
                AS $$
                BEGIN
                    IF OLD.entitlement_snapshot IS NOT NULL
                       AND (NEW.entitlement_snapshot::text IS DISTINCT FROM OLD.entitlement_snapshot::text) THEN
                        RAISE EXCEPTION 'entitlement_snapshot is immutable for qr_code %', OLD.id
                            USING ERRCODE = 'check_violation';
                    END IF;

                    RETURN NEW;
                END;
                $$;

                DROP TRIGGER IF EXISTS qr_codes_entitlement_snapshot_immutable ON qr_codes;

                CREATE TRIGGER qr_codes_entitlement_snapshot_immutable
                    BEFORE UPDATE ON qr_codes
                    FOR EACH ROW
                    EXECUTE FUNCTION prevent_qr_codes_entitlement_snapshot_mutation();
            SQL);

            return;
        }

        // SQLite (test driver) and any other DB without a procedural function
        // model: a single statement-level trigger with a WHEN guard.
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS qr_codes_entitlement_snapshot_immutable;

            CREATE TRIGGER qr_codes_entitlement_snapshot_immutable
                BEFORE UPDATE ON qr_codes
                FOR EACH ROW
                WHEN OLD.entitlement_snapshot IS NOT NULL
                     AND NEW.entitlement_snapshot IS NOT OLD.entitlement_snapshot
                BEGIN
                    SELECT RAISE(ABORT, 'entitlement_snapshot is immutable');
                END;
        SQL);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS qr_codes_entitlement_snapshot_immutable ON qr_codes;
                DROP FUNCTION IF EXISTS prevent_qr_codes_entitlement_snapshot_mutation();
            SQL);

            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS qr_codes_entitlement_snapshot_immutable;
        SQL);
    }
};
