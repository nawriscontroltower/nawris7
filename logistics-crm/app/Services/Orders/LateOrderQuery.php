<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * طلبات متأخرة بشكل عام (ليست شرطاً مع كابتن) — لصفحة متابعة التذاكر.
 * "أكثر من 3 أيام" = فرق أيام كامل ≥ 4 (أي أكثر من 3 أيام تقويمية كاملة).
 */
final class LateOrderQuery
{
    private const FOLLOW_UP_MIN_DELAY_DAYS = 4;

    public function followUpQueue(): Builder
    {
        return Order::query()
            ->whereNotIn('status', Order::TERMINAL_STATUSES)
            ->whereNotNull('promised_delivery_at')
            ->where('promised_delivery_at', '<', now())
            ->where(fn (Builder $q): void => OrderDelayScope::apply($q, self::FOLLOW_UP_MIN_DELAY_DAYS))
            ->with(['captain:id,code,full_name,phone', 'user:id,name,phone'])
            ->orderByDesc('promised_delivery_at');
    }

    public function getFollowUpQueue(): Collection
    {
        return $this->followUpQueue()->get();
    }
}
