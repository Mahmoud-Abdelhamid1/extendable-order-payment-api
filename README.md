# Order & Payment Management API

A Laravel REST API for managing orders and payments with a Strategy-pattern payment gateway system.

---

## Setup

### Requirements
- PHP 8.2+
- Composer
- MySQL / SQLite

### Installation

```bash
git clone <repo-url>
cd order-payment-api

composer install

cp .env.example .env
php artisan key:generate

# Configure your DB in .env, then:
php artisan migrate
```

### Running

```bash
php artisan serve
# API available at http://localhost:8000/api
```

### Testing

```bash
php artisan test
# or with coverage:
php artisan test --coverage
```

---

## Authentication

The API uses **Laravel Sanctum** (token-based). After registering or logging in, include the token in every protected request:

```
Authorization: Bearer <your-token>
```

---

## API Endpoints

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register a new user |
| POST | `/api/auth/login` | Login and get token |
| POST | `/api/auth/logout` | Revoke current token |

### Orders
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List orders (optional `?status=pending\|confirmed\|cancelled`) |
| POST | `/api/orders` | Create a new order |
| GET | `/api/orders/{id}` | Get a single order |
| PUT | `/api/orders/{id}` | Update order status or items |
| DELETE | `/api/orders/{id}` | Delete order (only if no payments) |

### Payments
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/orders/{id}/payments` | Process a payment for an order |
| GET | `/api/orders/{id}/payments` | List payments for an order |
| GET | `/api/payments` | List all payments |

---

## Adding a New Payment Gateway

The system uses the **Strategy Pattern**. Adding a new gateway requires **3 steps only**:

### Step 1 — Create the gateway class

```php
// app/Gateways/CryptoGateway.php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;

class CryptoGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $context = []): array
    {
        // Call your crypto payment SDK here
        // config('services.crypto.api_key') for credentials

        return [
            'status'           => 'successful',
            'gateway_response' => [
                'gateway'        => $this->getName(),
                'transaction_id' => 'CRYPTO-' . uniqid(),
                'amount'         => $amount,
                'processed_at'   => now()->toISOString(),
            ],
        ];
    }

    public function getName(): string
    {
        return 'crypto'; // Must match the payment_method value sent in the request
    }
}
```

### Step 2 — Register it in the provider

```php
// app/Providers/PaymentGatewayServiceProvider.php

$gateways = [
    CreditCardGateway::class,
    PaypalGateway::class,
    StripeGateway::class,
    CryptoGateway::class, // <-- add this line
];
```

### Step 3 — Add the value to the migration enum

```php
// In a new migration:
$table->enum('payment_method', ['credit_card', 'paypal', 'stripe', 'crypto']);
```

That's it. No changes to controllers, services, requests, or any other file.

---

## Gateway Configuration

Store gateway credentials in `.env` and access via `config('services.*')`:

```env
STRIPE_SECRET=sk_test_...
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
```

---

## Business Rules

- Orders are created with status `pending`
- Payments can only be processed for `confirmed` orders
- Orders with payments cannot be deleted
- Total is always calculated server-side (not trusted from client)

---

## Project Structure

```
app/
├── Contracts/PaymentGatewayInterface.php   ← Gateway contract
├── Gateways/                               ← One class per gateway
│   ├── CreditCardGateway.php
│   ├── PaypalGateway.php
│   └── StripeGateway.php
├── Providers/PaymentGatewayServiceProvider.php  ← Gateway registry
├── Services/
│   ├── OrderService.php
│   └── PaymentService.php
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
└── Models/
    ├── Order.php
    ├── OrderItem.php
    └── Payment.php
```
