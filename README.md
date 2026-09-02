# FoodBridge - Module 3: Request & Auto Matching

This Laravel module is owned by Tan Tai Wei (2301297). It lets authenticated Recipient users submit or withdraw food requests, automatically allocates eligible donations by preferred selection, category, expiry proximity, and first-come-first-served order, supports partial matches, and notifies both sides through an Observer-pattern publisher.

## Main implementation

- MVC: `RecipientController`, `FoodRequestController`, Eloquent entity models, and the Recipient Blade dashboard.
- Observer: `MatchPublisher`, `MatchObserver`, `DonorNotifier`, `RecipientNotifier`, and three concrete match-outcome events.
- Concurrency control: transactions, `lockForUpdate()`, deadlock retry, and database constraints.
- Web-service exposure: signed REST endpoints under `/api/v1/requests` with IFA request/response fields.
- Web-service consumption: signed calls to Module 1 identity and Module 2 donation services, with a three-second timeout, one retry, and response-envelope validation.
- Security: authenticated Recipient ownership, HMAC module authentication, timestamp freshness, replay-safe idempotency, throttling, validation, escaped output, and hidden internal fields.

## Run locally

Configure the database and a long random `MODULE_API_KEY`, then run:

```bash
php artisan migrate --seed
php artisan serve
```

Use PHP 8.3 or newer. In local mode, `/login` offers a seeded-recipient demonstration button; it is unavailable outside the local environment.

## Verification

```bash
vendor/bin/pint --test
php artisan test
```

The requirement-focused tests cover allocation rules, partial matching, Observer notifications, signed IFA requests, replay protection, authenticated ownership, service-consumption headers, and response-envelope validation.
