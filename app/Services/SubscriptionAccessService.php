<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class SubscriptionAccessService
{
    public function getLatestSubscription(User $user): ?Subscription
    {
        return $this->subscriptionQuery($user)
            ->latest()
            ->first();
    }

    public function getActiveSubscription(User $user): ?Subscription
    {
        return $this->subscriptionQuery($user)
            ->active()
            ->unexpired()
            ->latest()
            ->first();
    }

    public function userHasAccess(User $user, string $module): bool
    {
        return $this->resolveAccess($user, $module)['allowed'];
    }

    public function resolveAccess(User $user, ?string $module = null): array
    {
        $subscription = $this->getLatestSubscription($user);

        if (!$subscription) {
            return [
                'allowed' => false,
                'status' => 403,
                'message' => 'Subscription required to access this API.',
                'data' => null,
                'reason' => 'no_subscription',
                'required_module' => $module,
            ];
        }

        if ($subscription->status !== 'active') {
            return [
                'allowed' => false,
                'status' => 403,
                'message' => 'Your subscription is inactive.',
                'data' => null,
                'reason' => 'inactive_subscription',
                'required_module' => $module,
            ];
        }

        if ($subscription->isExpired()) {
            return [
                'allowed' => false,
                'status' => 403,
                'message' => 'Your subscription has expired.',
                'data' => null,
                'reason' => 'expired_subscription',
                'required_module' => $module,
            ];
        }

        if (is_null($module)) {
            return [
                'allowed' => true,
                'status' => 200,
                'message' => 'Access granted.',
                'data' => null,
                'reason' => 'active_subscription',
                'required_module' => null,
                'subscription' => $subscription,
            ];
        }

        if (!$this->featureTablesReady()) {
            return [
                'allowed' => true,
                'status' => 200,
                'message' => 'Access granted.',
                'data' => null,
                'reason' => 'legacy_subscription_access',
                'required_module' => $module,
                'subscription' => $subscription,
            ];
        }

        if (!$this->moduleExists($module)) {
            return [
                'allowed' => false,
                'status' => 500,
                'message' => 'Subscription module configuration is invalid.',
                'data' => null,
                'reason' => 'unknown_module',
                'required_module' => $module,
            ];
        }

        // Legacy zero-cost subscriptions predate the narrow free-tier feature
        // keys. They should still receive the two onboarding APIs.
        if ((float) ($subscription->plan?->amount ?? 1) <= 0 && in_array($module, [
            SubscriptionFeature::MODULE_IFSC_API,
            SubscriptionFeature::MODULE_INDIA_PINCODE_API,
        ], true)) {
            return [
                'allowed' => true,
                'status' => 200,
                'message' => 'Access granted.',
                'data' => null,
                'reason' => 'free_tier_access',
                'required_module' => $module,
                'subscription' => $subscription,
            ];
        }

        if (!$subscription->hasFeature($module)) {
            return [
                'allowed' => false,
                'status' => 403,
                'message' => 'Your current plan does not include this API category.',
                'data' => null,
                'reason' => 'module_not_allowed',
                'required_module' => $module,
            ];
        }

        return [
            'allowed' => true,
            'status' => 200,
            'message' => 'Access granted.',
            'data' => null,
            'reason' => 'allowed',
            'required_module' => $module,
            'subscription' => $subscription,
        ];
    }

    public function moduleExists(string $module): bool
    {
        if (!$this->featureTablesReady()) {
            return true;
        }

        $exists = SubscriptionFeature::query()
            ->where('key', $module)
            ->where('is_active', true)
            ->exists();

        // Keep API access checks compatible with installations that have run the
        // feature-table migrations but have not yet re-run the seeders.
        return $exists || in_array($module, [
            SubscriptionFeature::MODULE_ADDRESS_API,
            SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
            SubscriptionFeature::MODULE_STOCKS_MUTUAL_FUNDS_API,
            SubscriptionFeature::MODULE_ALL_API,
            SubscriptionFeature::MODULE_INDIA_PINCODE_API,
            SubscriptionFeature::MODULE_IFSC_API,
        ], true);
    }

    protected function subscriptionQuery(User $user): HasMany
    {
        $query = $user->subscriptions()->with('plan');

        if ($this->featureTablesReady()) {
            $query->with('plan.features');
        }

        return $query;
    }

    protected function featureTablesReady(): bool
    {
        return Schema::hasTable('subscription_features')
            && Schema::hasTable('plan_subscription_feature');
    }
}
