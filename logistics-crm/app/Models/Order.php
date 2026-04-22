<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELED = 'canceled';

    /** @var list<string> */
    public const TERMINAL_STATUSES = [self::STATUS_COMPLETED, self::STATUS_CANCELED];

    protected $fillable = [
        'reference',
        'user_id',
        'customer_phone',
        'captain_id',
        'status',
        'promised_delivery_at',
        'delivered_at',
        'notes',
        'follow_up_notes',
        'last_follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'promised_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'last_follow_up_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }
}
