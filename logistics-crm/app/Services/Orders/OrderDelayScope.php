<?php

namespace App\Services\Orders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * تطبيق شرط "عدد أيام التأخير ≥ عتبة" بشكل متوافق مع MySQL / PostgreSQL / SQLite.
 */
final class OrderDelayScope
{
    public static function apply(Builder $query, int $minDelayDays): void
    {
        $minDelayDays = max(0, $minDelayDays);
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $query->whereRaw(
                'FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - promised_delivery_at)) / 86400) >= ?',
                [$minDelayDays]
            );

            return;
        }

        if ($driver === 'sqlite') {
            $query->whereRaw(
                'CAST((julianday(\'now\') - julianday(promised_delivery_at)) AS INTEGER) >= ?',
                [$minDelayDays]
            );

            return;
        }

        $query->whereRaw('TIMESTAMPDIFF(DAY, promised_delivery_at, NOW()) >= ?', [$minDelayDays]);
    }
}
