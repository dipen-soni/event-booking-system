# 🎫 Event Booking System

A full-featured **Event Booking REST API** built with **Laravel 12**, featuring role-based access control, token authentication, queued notifications, and caching.

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Prerequisites](#-prerequisites)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Setup](#-database-setup)
- [Running the Application](#-running-the-application)
- [API Endpoints](#-api-endpoints)
- [Authentication](#-authentication)
- [Roles & Permissions](#-roles--permissions)
- [Testing](#-testing)
- [Postman Collection](#-postman-collection)
- [Project Structure](#-project-structure)

---

## ✨ Features

- **Authentication** — Token-based API auth via Laravel Sanctum
- **Role-Based Access Control** — Admin, Organizer, Customer roles via Spatie Permission
- **Event Management** — Full CRUD with search, filtering, and pagination
- **Ticket Management** — VIP, Standard, Economy ticket types per event
- **Booking System** — Book tickets with availability checking and double-booking prevention
- **Mock Payment** — Simulated payment gateway (80% success / 20% failure)
- **Notifications** — Queued email + database notifications on booking confirmation
- **Caching** — Frequently accessed events cached for 10 minutes
- **Admin Panel API** — Full CRUD endpoints for admin management
- **Comprehensive Testing** — 68 tests with 150 assertions

---

## 🛠 Tech Stack

| Component      | Technology                |
| -------------- | ------------------------- |
| Framework      | Laravel 12.52.0           |
| PHP            | 8.4+                      |
| Database       | MySQL                     |
| Authentication | Laravel Sanctum           |
| Authorization  | Spatie Laravel Permission |
| Queue          | Database driver           |
| Cache          | Database driver           |
| Testing        | PHPUnit                   |

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────┐
│                    API Routes                       │
│  Public: /register, /login, /events                 │
│  Auth:   /logout, /me, /bookings, /payments         │
│  Admin:  /admin/* (full CRUD)                       │
├─────────────────────────────────────────────────────┤
│                   Middleware                         │
│  auth:sanctum │ role:admin,organizer │ prevent.dbl   │
├─────────────────────────────────────────────────────┤
│                  Controllers                        │
│  Auth │ Api\Event │ Api\Ticket │ Api\Booking │ Admin │
├─────────────────────────────────────────────────────┤
│              Services & Traits                      │
│  PaymentService │ CommonQueryScopes                  │
├─────────────────────────────────────────────────────┤
│                    Models                           │
│  User │ Event │ Ticket │ Booking │ Payment           │
├─────────────────────────────────────────────────────┤
│                   Database                          │
│  MySQL with indexed tables + Spatie permission tbls  │
└─────────────────────────────────────────────────────┘
```

---

## 📦 Prerequisites

- **PHP** >= 8.2
- **Composer** >= 2.x
- **MySQL** >= 8.0 (or MariaDB >= 10.3)
- **Node.js** >= 18.x (optional, for frontend assets)

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/dipen-soni/event-booking-system.git
cd event-booking-system
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database

Edit `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_booking_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Create the Database

```sql
CREATE DATABASE event_booking_system;
```

### 6. Run Migrations & Seed

```bash
php artisan migrate --seed
```

This seeds:

- **2 Admins** — `admin@example.com` / `password`, `admin2@example.com` / `password`
- **3 Organizers** — random factory data
- **10 Customers** — random factory data
- **5 Events** — distributed across organizers
- **15 Tickets** — 3 per event (VIP, Standard, Economy)
- **20 Bookings** — random customers × tickets

---

## ⚙ Configuration

### Queue (for notifications)

The queue is configured to use the `database` driver. To process queued jobs:

```bash
php artisan queue:work
```

> **Note:** Without the queue worker running, notifications will remain in the `jobs` table until processed.

### Cache

Events are cached using the `database` driver with a 10-minute TTL. No additional configuration needed.

### Mail

By default, mail is set to `log` driver (writes to `storage/logs/laravel.log`). For real email delivery, update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

---

## ▶ Running the Application

```bash
# Start the development server
php artisan serve

# Start the queue worker (separate terminal)
php artisan queue:work

# The API is now available at:
# http://localhost:8000/api
```

---

## 📡 API Endpoints

### 🔐 Authentication

| Method | Endpoint        | Auth | Description                   |
| ------ | --------------- | ---- | ----------------------------- |
| `POST` | `/api/register` | ❌   | Register (customer/organizer) |
| `POST` | `/api/login`    | ❌   | Login, returns Bearer token   |
| `POST` | `/api/logout`   | ✅   | Revoke current token          |
| `GET`  | `/api/me`       | ✅   | Get current user with roles   |

### 🎪 Events

| Method   | Endpoint           | Auth         | Description                                     |
| -------- | ------------------ | ------------ | ----------------------------------------------- |
| `GET`    | `/api/events`      | ❌           | List events (paginated, searchable, filterable) |
| `GET`    | `/api/events/{id}` | ❌           | View event with tickets                         |
| `POST`   | `/api/events`      | ✅ Organizer | Create event                                    |
| `PUT`    | `/api/events/{id}` | ✅ Organizer | Update own event                                |
| `DELETE` | `/api/events/{id}` | ✅ Organizer | Delete own event                                |

**Query Parameters for `GET /api/events`:**
| Parameter | Example | Description |
|---|---|---|
| `search` | `?search=concert` | Search in title & description |
| `location` | `?location=delhi` | Filter by location |
| `date_from` | `?date_from=2026-03-01` | Events from this date |
| `date_to` | `?date_to=2026-06-01` | Events up to this date |
| `per_page` | `?per_page=10` | Results per page (max 100) |

### 🎫 Tickets

| Method   | Endpoint                         | Auth         | Description             |
| -------- | -------------------------------- | ------------ | ----------------------- |
| `POST`   | `/api/events/{event_id}/tickets` | ✅ Organizer | Create ticket for event |
| `PUT`    | `/api/tickets/{id}`              | ✅ Organizer | Update ticket           |
| `DELETE` | `/api/tickets/{id}`              | ✅ Organizer | Delete ticket           |

### 📋 Bookings

| Method | Endpoint                     | Auth    | Description                       |
| ------ | ---------------------------- | ------- | --------------------------------- |
| `POST` | `/api/tickets/{id}/bookings` | ✅ Auth | Book tickets (availability check) |
| `GET`  | `/api/bookings`              | ✅ Auth | List own bookings                 |
| `PUT`  | `/api/bookings/{id}/cancel`  | ✅ Auth | Cancel own booking                |

### 💳 Payments

| Method | Endpoint                     | Auth    | Description                     |
| ------ | ---------------------------- | ------- | ------------------------------- |
| `POST` | `/api/bookings/{id}/payment` | ✅ Auth | Mock payment (80% success rate) |
| `GET`  | `/api/payments/{id}`         | ✅ Auth | View payment details            |

### 👑 Admin Panel

All admin endpoints are prefixed with `/api/admin/` and require the `admin` role.

| Resource | Endpoints                                                                               |
| -------- | --------------------------------------------------------------------------------------- |
| Users    | `GET/POST /admin/users`, `GET/PUT/DELETE /admin/users/{id}`                             |
| Events   | `GET/POST /admin/events`, `GET/PUT/DELETE /admin/events/{id}`                           |
| Tickets  | `GET/POST /admin/events/{id}/tickets`, `GET/PUT/DELETE /admin/events/{id}/tickets/{id}` |
| Bookings | `GET/POST /admin/bookings`, `GET/PUT/DELETE /admin/bookings/{id}`                       |
| Payments | `GET/POST /admin/payments`, `GET/PUT/DELETE /admin/payments/{id}`                       |

---

## 🔑 Authentication

This API uses **Laravel Sanctum** for token-based authentication.

### Login & Get Token

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

**Response:**

```json
{
  "message": "Login successful.",
  "user": { "id": 1, "name": "Super Admin", ... },
  "access_token": "1|abc123xyz...",
  "token_type": "Bearer"
}
```

### Use Token in Requests

```bash
curl http://localhost:8000/api/me \
  -H "Authorization: Bearer 1|abc123xyz..." \
  -H "Accept: application/json"
```

---

## 👥 Roles & Permissions

Managed via **Spatie Laravel Permission**.

| Role          | Permissions                                                                  |
| ------------- | ---------------------------------------------------------------------------- |
| **Admin**     | Full access to all resources (users, events, tickets, bookings, payments)    |
| **Organizer** | Create/update/delete own events & tickets, read-only bookings for own events |
| **Customer**  | Browse events/tickets, book tickets, cancel bookings, view own payments      |

### Seeded Users

| Email                | Password   | Role  |
| -------------------- | ---------- | ----- |
| `admin@example.com`  | `password` | Admin |
| `admin2@example.com` | `password` | Admin |

---

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Suites

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Run a Specific Test File

```bash
php artisan test --filter=AuthTest
php artisan test --filter=PaymentServiceTest
```

### Test Summary

| File                      | Tests                        | Coverage                                      |
| ------------------------- | ---------------------------- | --------------------------------------------- |
| `Feature/AuthTest`        | 13                           | Registration, Login, Logout, Me               |
| `Feature/EventTest`       | 15                           | Browse, Search, Filter, CRUD, Roles           |
| `Feature/TicketTest`      | 7                            | CRUD, Ownership, Validation                   |
| `Feature/BookingTest`     | 11                           | Booking, Availability, Double-booking, Cancel |
| `Feature/PaymentTest`     | 10                           | Payment, Notifications, Ownership             |
| `Unit/PaymentServiceTest` | 10                           | processPayment(), processRefund()             |
| **Total**                 | **68 tests, 150 assertions** | **All passing ✅**                            |

---

## 📬 Postman Collection

A ready-to-use Postman collection is included:

```
postman/Event_Booking_System.postman_collection.json
```

### Import

1. Open **Postman**
2. Click **Import** → **Upload Files**
3. Select the JSON file above
4. Start with **Login (Admin)** — the token auto-populates for all other requests

### Features

- ✅ **39 requests** organized into folders
- ✅ **Auto-token capture** on login/register
- ✅ **Auto-ID capture** for event, ticket, booking, payment IDs
- ✅ **Pre-filled request bodies** for quick testing
- ✅ **Documented descriptions** for each request

---

## 📁 Project Structure

```
event-booking-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Admin CRUD controllers
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── EventController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── TicketController.php
│   │   │   │   └── UserController.php
│   │   │   ├── Api/             # Public API controllers
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── EventController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   └── TicketController.php
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── Customer/       # Customer-specific controllers
│   │   │   └── Organizer/      # Organizer-specific controllers
│   │   └── Middleware/
│   │       ├── CheckRole.php           # Role-based access
│   │       └── PreventDoubleBooking.php # Double booking protection
│   ├── Models/
│   │   ├── User.php
│   │   ├── Event.php
│   │   ├── Ticket.php
│   │   ├── Booking.php
│   │   └── Payment.php
│   ├── Notifications/
│   │   └── BookingConfirmedNotification.php  # Queued notification
│   ├── Services/
│   │   └── PaymentService.php    # Mock payment processing
│   └── Traits/
│       └── CommonQueryScopes.php # Reusable query scopes
├── database/
│   ├── factories/               # Model factories
│   ├── migrations/              # Table schemas with indexes
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolePermissionSeeder.php  # Roles & permissions
│       ├── UserSeeder.php            # 2 admins, 3 organizers, 10 customers
│       ├── EventSeeder.php           # 5 events, 15 tickets
│       └── BookingSeeder.php         # 20 bookings
├── routes/
│   └── api.php                  # All API routes
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php
│   │   ├── EventTest.php
│   │   ├── TicketTest.php
│   │   ├── BookingTest.php
│   │   └── PaymentTest.php
│   └── Unit/
│       └── PaymentServiceTest.php
├── postman/
│   └── Event_Booking_System.postman_collection.json
└── README.md
```

---

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
