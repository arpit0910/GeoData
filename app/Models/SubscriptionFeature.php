<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriptionFeature extends Model
{
    use HasFactory;

    public const MODULE_ADDRESS_API = 'address_api';
    public const MODULE_BANKING_CURRENCY_API = 'banking_currency_api';
    public const MODULE_STOCKS_MUTUAL_FUNDS_API = 'stocks_mutual_funds_api';
    public const MODULE_ALL_API = 'all_api';
    public const MODULE_INDIA_PINCODE_API = 'india_pincode_api';
    public const MODULE_IFSC_API = 'ifsc_api';

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            Plan::class,
            'plan_subscription_feature'
        )->withTimestamps();
    }
}
