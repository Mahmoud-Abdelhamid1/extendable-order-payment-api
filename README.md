# Order & Payment Management API

A Laravel REST API for managing orders and payments, built with clean-code principles and a **Strategy Pattern** payment gateway system that makes adding new gateways a one-file change.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Setup](#setup)
3. [Running the API](#running-the-api)
4. [Running Tests](#running-tests)
5. [Authentication](#authentication)
6. [API Endpoints](#api-endpoints)
7. [Business Rules](#business-rules)
8. [Payment Gateway Extensibility](#payment-gateway-extensibility)
9. [Gateway Configuration](#gateway-configuration)
10. [Project Structure](#project-structure)
11. [Assumptions & Notes](#assumptions--notes)

---

## Requirements

- PHP 8.3+
- Composer 2.x
- SQLite (default, zero-config) **or** MySQL / PostgreSQL

---

## Setup

```bash
# 1. Clone the repository
git clone <repo-url>
cd order-payment-api

# 2. Install PHP dependencies
composer install

# 3. Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# 4. Run database migrations
#    Uses SQLite by default (database/database.sqlite is auto-created)
php artisan migrate

# To use MySQL instead, edit .env:
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=order_payment_api
#   DB_USERNAME=root
#   DB_PASSWORD=secret
```

---

## Running the API

```bash
php artisan serve
# API base URL: http://localhost:8000/api
```

---

## Running Tests

```bash
# Run all tests
php artisan test

# Run a specific test class
php artisan test --filter AuthTest
php artisan test --filter OrderTest
php artisan test --filter PaymentTest

# With coverage report (requires Xdebug or PCOV)
php artisan test --coverage
```

All tests use an **in-memory SQLite database** (via `RefreshDatabase`) so they are completely isolated from your development database.

---

## Authentication

The API uses **token-based authentication** via [Laravel Sanctum](https://laravel.com/docs/sanctum).

After registering or logging in, include the returned token in every protected request:

```
Authorization: Bearer <your-token>
```

Public endpoints (no token required):
- `POST /api/auth/register`
- `POST /api/auth/login`

All other endpoints require a valid Bearer token.

---

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register` | ❌ | Register a new user, returns token |
| `POST` | `/api/auth/login` | ❌ | Login, returns token |
| `POST` | `/api/auth/logout` | ✅ | Revoke the current token |

#### Register — `POST /api/auth/register`

**Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response `201`:**
```json
{
  "message": "Registration successful.",
  "token": "1|abc123...",
  "user": { "id": 1, "name": "Jane Doe", "email": "jane@example.com" }
}
```

**Error `422`** — validation failure (e.g. duplicate email, password too short).

---

#### Login — `POST /api/auth/login`

**Request:**
```json
{
  "email": "jane@example.com",
  "password": "secret123"
}
```

**Response `200`:**
```json
{
  "message": "Login successful.",
  "token": "2|xyz789...",
  "user": { "id": 1, "name": "Jane Doe", "email": "jane@example.com" }
}
```

**Error `401`** — invalid credentials.

---

#### Logout — `POST /api/auth/logout`

**Headers:** `Authorization: Bearer <token>`

**Response `200`:**
```json
{ "message": "Logged out successfully." }
```

---

### Orders

All order endpoints require authentication.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/orders` | List all orders (paginated) |
| `GET` | `/api/orders?status=pending` | Filter by status (`pending`, `confirmed`, `cancelled`) |
| `POST` | `/api/orders` | Create a new order |
| `GET` | `/api/orders/{id}` | Get a single order with items & payments |
| `PUT` | `/api/orders/{id}` | Update order status and/or items |
| `DELETE` | `/api/orders/{id}` | Delete order (only if no payments exist) |

#### Create Order — `POST /api/orders`

**Request:**
```json
{
  "items": [
    { "product_name": "Widget A", "quantity": 2, "price": 15.00 },
    { "product_name": "Widget B", "quantity": 1, "price": 25.00 }
  ]
}
```

**Response `201`:**
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "status": "pending",
    "total": 55.00,
    "items": [
      { "id": 1, "product_name": "Widget A", "quantity": 2, "price": 15.00, "subtotal": 30.00 },
      { "id": 2, "product_name": "Widget B", "quantity": 1, "price": 25.00, "subtotal": 25.00 }
    ],
    "created_at": "2026-06-27T20:00:00.000000Z",
    "updated_at": "2026-06-27T20:00:00.000000Z"
  }
}
```

> **Note:** `total` is **always calculated server-side** — the client must not pass it.

**Error `422`** — items missing, invalid quantity/price.

---

#### Update Order — `PUT /api/orders/{id}`

Updates order status. Optionally replaces all items (old items are deleted and recreated).

**Request:**
```json
{
  "status": "confirmed",
  "items": [
    { "product_name": "Widget C", "quantity": 3, "price": 10.00 }
  ]
}
```

**Response `200`:** Updated `OrderResource`.

---

#### Delete Order — `DELETE /api/orders/{id}`

**Response `200`:**
```json
{ "message": "Order deleted successfully." }
```

**Error `422`** — order has associated payments and cannot be deleted.

---

### Payments

All payment endpoints require authentication.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/orders/{id}/payments` | Process a payment for a confirmed order |
| `GET` | `/api/orders/{id}/payments` | List all payments for an order (paginated) |
| `GET` | `/api/payments` | List all payments across all orders (paginated) |

#### Process Payment — `POST /api/orders/{id}/payments`

Supported `payment_method` values: `credit_card`, `paypal`, `stripe`

**Request:**
```json
{
  "payment_method": "credit_card"
}
```

**Response `201`:**
```json
{
  "data": {
    "id": 1,
    "order_id": 1,
    "payment_method": "credit_card",
    "status": "successful",
    "gateway_response": {
      "gateway": "credit_card",
      "transaction_id": "CC-A1B2C3D4",
      "amount": 55.00,
      "currency": "USD",
      "processed_at": "2026-06-27T20:00:00.000000Z",
      "message": "Charge approved"
    },
    "created_at": "2026-06-27T20:00:00.000000Z"
  }
}
```

**Error `422`** — order is not in `confirmed` status, or `payment_method` is unsupported.

---

## Business Rules

| Rule | Detail |
|------|--------|
| New orders start as `pending` | Status is set by the server on creation |
| Payments require `confirmed` status | Attempting to pay a `pending` or `cancelled` order returns `422` |
| Orders with payments cannot be deleted | Returns `422` with a descriptive message |
| Total is server-side only | Calculated from `quantity × price` per item; client cannot inject a total |

---

## Payment Gateway Extensibility

The system uses the **Strategy Pattern** to decouple payment logic from business logic. Each gateway is a standalone class implementing a shared interface. Adding a new gateway requires **exactly 3 steps** with no changes to controllers, services, models, or tests.

### Step 1 — Create the Gateway Class

```php
// app/Gateways/CryptoGateway.php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;

class CryptoGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $context = []): array
    {
        // In production: call your crypto payment SDK here
        // $apiKey = config('services.crypto.api_key');

        $success = $amount > 0;

        return [
            'status'           => $success ? 'successful' : 'failed',
            'gateway_response' => [
                'gateway'        => $this->getName(),
                'transaction_id' => 'CRYPTO-' . strtoupper(uniqid()),
                'amount'         => $amount,
                'currency'       => 'USD',
                'processed_at'   => now()->toISOString(),
                'message'        => $success ? 'Payment confirmed on-chain' : 'Payment failed',
            ],
        ];
    }

    public function getName(): string
    {
        return 'crypto'; // Must match the value sent as payment_method in the request
    }
}
```

### Step 2 — Register in the Service Provider

```php
// app/Providers/PaymentGatewayServiceProvider.php

$gateways = [
    CreditCardGateway::class,
    PaypalGateway::class,
    StripeGateway::class,
    CryptoGateway::class, // ← add this line only
];
```

### Step 3 — Add to the Migration Enum

Create a new migration to extend the allowed values:

```php
// database/migrations/xxxx_add_crypto_to_payment_method_enum.php

Schema::table('payments', function (Blueprint $table) {
    $table->enum('payment_method', ['credit_card', 'paypal', 'stripe', 'crypto'])
          ->change();
});
```

**That's it.** The `ProcessPaymentRequest` validation and `PaymentService` gateway resolution automatically pick up the new gateway — no other files touched.

---

## Gateway Configuration

Store credentials in `.env` and read them in the gateway class via `config('services.*')`:

```env
# .env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...

PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_MODE=sandbox

CREDIT_CARD_API_KEY=...
CREDIT_CARD_MERCHANT_ID=...
```

Then in `config/services.php`:

```php
'stripe'      => ['secret' => env('STRIPE_SECRET')],
'paypal'      => [
    'client_id'     => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    'mode'          => env('PAYPAL_MODE', 'sandbox'),
],
'credit_card' => ['api_key' => env('CREDIT_CARD_API_KEY')],
```

Access in a gateway:

```php
$secret = config('services.stripe.secret');
```

---

## Project Structure

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php        ← Gateway contract (Strategy interface)
│
├── Gateways/                              ← One class per payment gateway
│   ├── CreditCardGateway.php
│   ├── PaypalGateway.php
│   └── StripeGateway.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   └── PaymentGatewayServiceProvider.php  ← Gateway registry (IoC binding)
│
├── Services/
│   ├── OrderService.php                   ← Order business logic
│   └── PaymentService.php                 ← Payment orchestration & gateway resolution
│
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── OrderController.php
│   │   └── PaymentController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   ├── Order/
│   │   │   ├── StoreOrderRequest.php
│   │   │   └── UpdateOrderRequest.php
│   │   └── Payment/
│   │       └── ProcessPaymentRequest.php
│   └── Resources/
│       ├── OrderResource.php
│       ├── OrderItemResource.php
│       └── PaymentResource.php
│
└── Models/
    ├── User.php
    ├── Order.php
    ├── OrderItem.php
    └── Payment.php

tests/
└── Feature/
    ├── AuthTest.php      (7 tests)
    ├── OrderTest.php     (7 tests)
    └── PaymentTest.php   (7 tests)
```

---

## Assumptions & Notes

- **Authentication:** Uses [Laravel Sanctum](https://laravel.com/docs/sanctum) for stateless token-based auth. The `php-open-source-saver/jwt-auth` package in `composer.json` is an unused dependency from the initial scaffold and can be removed.
- **Payment simulation:** All three gateways simulate processing — no real API calls are made. The `gateway_response` field captures what a real gateway response would look like for inspection.
- **Pagination:** All list endpoints return 10 results per page with standard Laravel pagination metadata (`data`, `links`, `meta`).
- **Soft deletes:** Not implemented — a deleted order is permanently removed from the database (subject to the no-payments constraint).
- **Authorization:** All authenticated users can manage all orders/payments (no per-user ownership check). This can be extended with `Gate`/`Policy` if multi-user isolation is needed.
