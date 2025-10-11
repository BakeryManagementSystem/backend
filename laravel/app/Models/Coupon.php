<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the coupon is valid.
     */
    public function isValid(): bool
    {
        return $this->is_active
            && $this->valid_from <= now()
            && $this->valid_until >= now()
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    /**
     * Check if the coupon can be applied to a given amount.
     */
    public function canBeApplied(float $amount): bool
    {
        return $this->isValid() && $amount >= $this->minimum_amount;
    }

    /**
     * Calculate the discount amount for a given total.
     */
    public function calculateDiscount(float $amount): float
    {
        if (!$this->canBeApplied($amount)) {
            return 0;
        }

        $discount = $this->type === 'percentage'
            ? ($amount * $this->value / 100)
            : $this->value;

        if ($this->maximum_discount !== null && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        return round($discount, 2);
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}

