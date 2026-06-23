<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'total',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total' => 'decimal:2',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payments for the order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // -----------------------------------------------------------------------
    // Business helpers
    // -----------------------------------------------------------------------

    /**
     * Determine whether the order can be deleted.
     * Orders with associated payments cannot be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this->payments()->doesntExist();
    }

    /**
     * Determine whether a payment can be processed for this order.
     * Only confirmed orders can be paid.
     */
    public function canBeCharged(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Recalculate and persist the order total from its items.
     */
    public function recalculateTotal(): void
    {
        $total = $this->items()->selectRaw('SUM(quantity * price) as total')->value('total') ?? 0;

        $this->update(['total' => $total]);
    }
}
