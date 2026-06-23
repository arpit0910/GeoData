# Subscription Module Architecture

This project now supports feature-based subscription access and reusable plan benefits on top of the existing `plans` and `subscriptions` tables.

## Current module keys

- `address_api`
- `banking_currency_api`
- `stocks_mutual_funds_api`
- `all_api`

## Database design

- `plans` remains the source of pricing, billing cycle, and credit limits.
- `subscriptions` remains the source of purchased plan records, status, credits, and expiry.
- `subscription_features` stores module definitions.
- `plan_subscription_feature` maps one plan to one or more module keys.
- `benefits` stores reusable plan benefit records.
- `benefit_plan` maps one plan to one or more selectable benefits.

Existing plans are backfilled with `all_api` during migration so current customers keep access after deployment.
Existing plan `benefits` JSON values are migrated into reusable `benefits` records and linked through the pivot table. The JSON column is still kept in sync for backward compatibility with older views and payloads.

## URL compatibility

The API route implementation is now split internally into category files:

- `routes/api/address.php`
- `routes/api/banking-currency.php`
- `routes/api/stocks-mutual-funds.php`

Public endpoint URLs did not change. Existing paths such as `/api/v1/regions`, `/api/v1/banks`, and `/api/v1/equities` remain valid.

## Access flow

- Route middleware alias: `subscription`
- Middleware class: `App\Http\Middleware\CheckSubscriptionFeature`
- Service class: `App\Services\SubscriptionAccessService`

Examples:

```php
Route::middleware(['auth:sanctum', 'subscription:address_api', 'api.credits'])
    ->group(base_path('routes/api/address.php'));

Route::middleware(['auth:sanctum', 'subscription:banking_currency_api', 'api.credits'])
    ->group(base_path('routes/api/banking-currency.php'));
```

The middleware allows access when:

- the user has a subscription
- the subscription status is `active`
- the subscription is not expired
- the plan contains the requested module
- or the plan contains `all_api`

If access is denied, the API returns a `403 Forbidden` response describing the required module.

## Benefit assignment

Plans can now be linked to reusable benefit records instead of manually typed benefit text per plan.

- Admin plan forms select benefits using `benefit_ids[]`.
- The `Plan` model resolves linked benefit names through `resolvedBenefits()`.
- Pricing and subscription pages continue to render benefits without changing their public URLs.

To add a new reusable benefit:

1. Create a record in `benefits`.
2. Mark it active.
3. Assign it to one or more plans through the admin plan form or the `benefit_plan` pivot.

## Adding a new API module

1. Create a new `subscription_features` record, for example `gst_api`.
2. Assign that feature to one or more plans using the `plan_subscription_feature` pivot.
3. Protect the routes with `subscription:gst_api`.

Example:

```php
Route::middleware(['auth:sanctum', 'subscription:gst_api', 'api.credits'])
    ->group(base_path('routes/api/gst.php'));
```

No middleware code changes are required for new module keys once the feature exists in the database.
