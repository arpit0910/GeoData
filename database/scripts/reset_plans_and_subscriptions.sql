-- Safely reset plans and subscriptions while retaining customers, coupons,
-- API logs, benefits, subscription features, and transaction audit rows.
-- Run this against the intended database only after taking a backup.

START TRANSACTION;

-- Preserve unrelated/customer records while removing their old plan balance.
UPDATE users
SET plan_id = NULL,
    available_credits = 0
WHERE plan_id IS NOT NULL
   OR available_credits <> 0;

-- Preserve coupons and historical records, but detach deleted references.
UPDATE coupons
SET plan_id = NULL
WHERE plan_id IS NOT NULL;

UPDATE api_logs
SET subscription_id = NULL
WHERE subscription_id IS NOT NULL;

UPDATE transaction_histories
SET subscription_id = NULL,
    plan_id = NULL
WHERE subscription_id IS NOT NULL
   OR plan_id IS NOT NULL;

UPDATE coupon_user
SET subscription_id = NULL
WHERE subscription_id IS NOT NULL;

-- Clear subscription and plan-owned data in foreign-key-safe order.
DELETE FROM subscriptions;
DELETE FROM plan_subscription_feature;
DELETE FROM benefit_plan;
DELETE FROM plans;

COMMIT;

-- Optional ID reset. These statements intentionally run after COMMIT because
-- ALTER TABLE causes an implicit commit in MySQL.
ALTER TABLE subscriptions AUTO_INCREMENT = 1;
ALTER TABLE plans AUTO_INCREMENT = 1;

-- Verify that only the intended tables were cleared.
SELECT COUNT(*) AS subscriptions_remaining FROM subscriptions;
SELECT COUNT(*) AS plans_remaining FROM plans;
