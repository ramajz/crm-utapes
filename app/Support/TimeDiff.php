<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ekspresi mentah untuk selisih waktu dalam MENIT antara dua kolom timestamp.
 *
 * SQLite pakai JULIANDAY(), PostgreSQL pakai EXTRACT(EPOCH FROM ...).
 * Sebelumnya pakai JULIANDAY mentah — pecah di PostgreSQL (prod).
 */
class TimeDiff
{
    public static function minutesBetween(string $endExpr, string $startExpr): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // EXTRACT(EPOCH FROM (a - b)) = detik → bagi 60 = menit
            return sprintf(
                'SUM(EXTRACT(EPOCH FROM ((%s)::timestamp - (%s)::timestamp)) / 60)',
                $endExpr,
                $startExpr
            );
        }

        // SQLite (local dev)
        return sprintf(
            'SUM((JULIANDAY(%s) - JULIANDAY(%s)) * 24 * 60)',
            $endExpr,
            $startExpr
        );
    }

    /**
     * Versi tanpa SUM — untuk CASE WHEN per baris.
     */
    public static function minutesExpr(string $endExpr, string $startExpr): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return sprintf(
                '(EXTRACT(EPOCH FROM ((%s)::timestamp - (%s)::timestamp)) / 60)',
                $endExpr,
                $startExpr
            );
        }

        return sprintf(
            '((JULIANDAY(%s) - JULIANDAY(%s)) * 24 * 60)',
            $endExpr,
            $startExpr
        );
    }
}
