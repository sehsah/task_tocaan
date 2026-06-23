# Order & Payment Management API

A production-ready RESTful API built with **Laravel 11** for managing orders and payments. Features JWT authentication, a **Strategy Pattern** for extensible payment gateways, full validation, pagination, and comprehensive test coverage.

---

## Table of Contents

- [Requirements](#requirements)
- [Setup Instructions](#setup-instructions)
- [API Overview](#api-overview)
- [Payment Gateway Extensibility](#payment-gateway-extensibility)
- [Running Tests](#running-tests)
- [Postman Collection](#postman-collection)
- [Assumptions & Notes](#assumptions--notes)

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ (or any Laravel-compatible database)
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`

---

## Setup Instructions

### 1. Clone the Repository

```bash
git clone <repository-url>
cd <project-directory>
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and set your database credentials:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_payment_api
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Generate JWT Secret

```bash
php artisan jwt:secret
```

### 6. Create the Database

```sql
CREATE DATABASE order_payment_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Start the Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`.

---

## API Overview

All API endpoints are prefixed with `/api`. All protected endpoints require the `Authorization: Bearer <token>` header.

### Authentication

| Method | Endpoint | Auth Required | Description |
|--------|----------|:---:|-------------|
| POST | `/api/auth/register` | ❌ | Register a new user |
| POST | `/api/auth/login` | ❌ | Login and receive JWT |
| POST | `/api/auth/logout` | ✅ | Invalidate the JWT token |
| GET | `/api/auth/me` | ✅ | Get the current user |

### Orders

| Method | Endpoint | Auth Required | Description |
|--------|----------|:---:|-------------|
| GET | `/api/orders` | ✅ | List orders (paginated, filterable by `?status=`) |
| POST | `/api/orders` | ✅ | Create an order with items |
| GET | `/api/orders/{id}` | ✅ | Get a single order |
| PUT | `/api/orders/{id}` | ✅ | Update an order |
| DELETE | `/api/orders/{id}` | ✅ | Delete an order (no payments must exist) |
| GET | `/api/orders/{id}/payments` | ✅ | List payments for an order |

#### Order Status Values
`pending` | `confirmed` | `cancelled`

### Payments

| Method | Endpoint | Auth Required | Description |
|--------|----------|:---:|-------------|
| POST | `/api/payments` | ✅ | Process a payment for a confirmed order |
| GET | `/api/payments` | ✅ | List all payments (paginated) |
| GET | `/api/payments/{id}` | ✅ | Get a single payment |

#### Supported Payment Methods
`credit_card` | `paypal` | `stripe`

#### Business Rules
- ✅ Payments can **only** be processed for orders with `confirmed` status
- ✅ Orders with associated payments **cannot** be deleted

---

## Payment Gateway Extensibility

The payment system uses the **Strategy Pattern** to make adding new gateways trivially easy.

### Architecture

```
app/Services/Payment/
├── Contracts/
│   └── PaymentGatewayInterface.php   ← The contract every gateway implements
├── Exceptions/
│   └── UnsupportedGatewayException.php
├── Gateways/
│   ├── CreditCardGateway.php
│   ├── PayPalGateway.php
│   └── StripeGateway.php             ← Each gateway is isolated here
├── PaymentGatewayFactory.php          ← Central resolver (1 line per gateway)
└── PaymentService.php                 ← Orchestrates processing
```

### How to Add a New Gateway

**Step 1:** Create a new class implementing `PaymentGatewayInterface`:

```php
// app/Services/Payment/Gateways/CryptoGateway.php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;

class CryptoGateway implements PaymentGatewayInterface
{
    public function process(array $paymentData): array
    {
        // Your payment logic here
        return [
            'gateway'        => $this->getName(),
            'status'         => 'successful',
            'transaction_id' => 'CRYPTO-' . strtoupper(uniqid()),
            'message'        => 'Crypto payment confirmed.',
            'processed_at'   => now()->toIso8601String(),
        ];
    }

    public function getName(): string
    {
        return 'crypto';
    }
}
```

**Step 2:** Register it in the factory (`app/Services/Payment/PaymentGatewayFactory.php`) — add **one line**:

```php
return match ($method) {
    'credit_card' => new CreditCardGateway(),
    'paypal'      => new PayPalGateway(),
    'stripe'      => new StripeGateway(),
    'crypto'      => new CryptoGateway(),   // ← Add this line
    default => throw new UnsupportedGatewayException($method),
};
```

**That's it.** No other files need to change. The `StorePaymentRequest` validation dynamically reads supported gateways from `PaymentGatewayFactory::supported()`.

**Step 3 (Optional):** Add gateway-specific configuration to `config/payment-gateways.php` and `.env`:

```ini
CRYPTO_API_KEY=your_key_here
CRYPTO_API_SECRET=your_secret
```

### Gateway Simulation Logic

Since this is a simulated implementation:

| Gateway | Success Condition |
|---------|-------------------|
| `credit_card` | Order total is an even number |
| `paypal` | Order total < 1000 |
| `stripe` | Always succeeds (test mode) |

Override any gateway result via `.env`:
```ini
STRIPE_FORCE_STATUS=failed
CREDIT_CARD_FORCE_STATUS=successful
```

---

## Running Tests

Tests use an **in-memory SQLite** database — no setup needed.

```bash
# Run all tests
php artisan test

# Run with coverage report
php artisan test --coverage

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature
```

### Test Coverage

| Suite | Tests | What's Covered |
|-------|-------|----------------|
| **Unit** | 14 | All 3 gateway classes, PaymentGatewayFactory |
| **Feature** | 34 | AuthController, OrderController, PaymentController |
| **Total** | **48** | Full business rules, validation, pagination |

---

## Postman Collection

Import `postman_collection.json` into Postman:

1. Open Postman → **Import** → select `postman_collection.json`
2. Set the `base_url` collection variable to `http://localhost:8000`
3. Run **Register** or **Login** — the token is **automatically captured** into `auth_token`
4. All subsequent requests use `Bearer {{auth_token}}` automatically

---

## Assumptions & Notes

1. **Authentication Scope**: All order and payment endpoints require authentication. Orders are associated with the authenticated user via `user_id`.

2. **Order Ownership**: The API does not restrict viewing/editing orders to their owner — any authenticated user can view and modify any order. Add a policy if ownership restriction is needed.

3. **Payment Simulation**: All gateways simulate payment processing. No real API calls are made. Use `FORCE_STATUS` env vars to control outcomes in development.

4. **Multiple Payments per Order**: The system allows multiple payment attempts per order (e.g., retry after failure). There is no restriction preventing a second payment on an already-paid order.

5. **Decimal Precision**: All monetary values use `decimal(10,2)` in the database.

6. **Laravel Version**: Built on Laravel 13 (the latest stable release at time of development).

7. **PSR-12 Compliance**: Code follows PSR-12 standards enforced by `laravel/pint`. Run `./vendor/bin/pint` to auto-format.
