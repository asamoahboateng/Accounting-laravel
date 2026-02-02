# QuickBooks Clone

A comprehensive, enterprise-ready SaaS accounting and financial management application built with Laravel, Filament, and Livewire. This application replicates core QuickBooks Online Advanced functionality with multi-tenancy support, triple-entry bookkeeping, and advanced reporting capabilities.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Structure](#database-structure)
- [Architecture](#architecture)
- [Usage Guide](#usage-guide)
- [Multi-Tenancy](#multi-tenancy)
- [Theme Customization](#theme-customization)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

### Financial Management
- **Multi-Company Support** - Complete data isolation between companies/tenants
- **Chart of Accounts** - Hierarchical account structure with types and subtypes
- **Triple-Entry Bookkeeping** - Transaction → Journal Entry → Audit Log chain
- **Fiscal Year Management** - Configurable fiscal years and periods
- **Multi-Currency** - Support for 30+ currencies with exchange rate tracking
- **Books Close Process** - Period closing with anomaly detection

### Sales & Invoicing
- **Invoice Management** - Create, send, and track customer invoices
- **Interactive Invoice Builder** - Real-time Livewire-powered invoice creation
- **Sales Estimates** - Create estimates and convert to invoices
- **Payment Tracking** - Record and apply customer payments
- **Accounts Receivable** - Track outstanding customer balances
- **Recurring Invoices** - Automated invoice scheduling

### Expenses & Purchasing
- **Bill Management** - Track vendor bills and expenses
- **Purchase Orders** - Create and manage purchase orders
- **Payment Processing** - Record payments to vendors
- **Accounts Payable** - Monitor outstanding vendor balances
- **Recurring Bills** - Automated expense scheduling

### Banking
- **Bank Accounts** - Manage multiple bank accounts
- **Bank Reconciliation** - Interactive reconciliation interface
- **Transaction Import** - Import and categorize bank transactions
- **Bank Rules** - Automated transaction categorization
- **Bank Connections** - Integration-ready bank connection management

### Inventory
- **Product Management** - Products, services, and bundles
- **Multi-Location Inventory** - Track inventory across warehouses
- **Inventory Movements** - Complete movement history
- **Moving Average Cost** - MAC inventory valuation
- **Units of Measure** - Configurable measurement units

### Reporting & Analytics
- **Financial Dashboard** - Real-time financial overview widgets
- **Cash Flow Charts** - Visual cash flow tracking
- **AR/AP Aging** - Receivable and payable aging reports
- **Custom Reports** - Configurable report builder
- **Scheduled Reports** - Automated report generation
- **Report Snapshots** - Historical report preservation
- **Anomaly Detection** - AI-powered transaction anomaly identification

### Administration
- **User Management** - Role-based access control with Spatie Permissions
- **Super Admin** - System-wide administrative access
- **User Impersonation** - Support user assistance feature
- **Activity Logging** - Complete audit trail
- **Theme Customization** - Per-company theme settings
- **Mileage Tracking** - Vehicle and mileage expense tracking

## Tech Stack

### Backend
| Package | Version | Purpose |
|---------|---------|---------|
| Laravel | ^12.0 | PHP Framework |
| Filament | ^3.3 | Admin Panel |
| Livewire | ^3.6 | Reactive Components |
| spatie/laravel-permission | ^6.24 | Roles & Permissions |
| spatie/laravel-activitylog | ^4.11 | Activity Logging |
| brick/money | ^0.11.0 | Money Calculations |
| predis/predis | ^3.3 | Redis Client |

### Frontend
| Package | Version | Purpose |
|---------|---------|---------|
| Tailwind CSS | ^4.0 | Styling |
| Vite | ^7.0 | Build Tool |
| Axios | ^1.11 | HTTP Client |

### Development
| Package | Version | Purpose |
|---------|---------|---------|
| PHPUnit | ^11.5 | Testing |
| Laravel Pint | ^1.24 | Code Formatting |
| Laravel Sail | ^1.41 | Docker Environment |

## Requirements

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- SQLite / MySQL 8.0+ / PostgreSQL 14+
- Redis (optional, for caching/queues)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/quick-book-laravel.git
cd quick-book-laravel
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database

Edit `.env` file with your database credentials:

```env
# SQLite (for development)
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Or MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quickbook
DB_USERNAME=root
DB_PASSWORD=

# Or PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=quickbook
DB_USERNAME=postgres
DB_PASSWORD=
```

### 5. Run Migrations and Seeders

```bash
# Create SQLite database file (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed initial data (currencies, account types, etc.)
php artisan db:seed
```

### 6. Build Frontend Assets

```bash
npm run build

# Or for development with hot reload
npm run dev
```

### 7. Start the Application

```bash
php artisan serve
```

Visit `http://localhost:8000/admin` to access the application.

## Configuration

### Session Configuration

For proper session handling (especially for impersonation):

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### Cache Configuration

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## Database Structure

### Core Tables (32 migrations)

#### Company & Users
- `companies` - Multi-tenant company profiles
- `users` - System users with super admin flag
- `company_user` - Pivot table for user-company relationships

#### Accounting
- `currencies` - Supported currencies
- `account_types` - Account type classifications
- `account_subtypes` - Detailed account subtypes
- `accounts` - Chart of accounts
- `fiscal_years` - Fiscal year definitions
- `fiscal_periods` - Periods within fiscal years
- `transactions` - General transactions
- `journal_entries` - Double/triple entry journals
- `journal_entry_lines` - Journal line items

#### Sales
- `contacts` - Customers and vendors
- `contact_persons` - Contact persons within organizations
- `invoices` - Customer invoices
- `invoice_lines` - Invoice line items
- `estimates` - Sales estimates
- `estimate_lines` - Estimate line items
- `payments_received` - Customer payments
- `payment_applications` - Payment to invoice applications

#### Expenses
- `bills` - Vendor bills
- `bill_lines` - Bill line items
- `purchase_orders` - Purchase orders
- `purchase_order_lines` - PO line items
- `payments_made` - Vendor payments
- `bill_payment_applications` - Payment to bill applications

#### Banking
- `bank_accounts` - Company bank accounts
- `bank_connections` - Bank integration connections
- `bank_transactions` - Imported transactions
- `bank_rules` - Categorization rules
- `reconciliations` - Reconciliation records
- `reconciliation_items` - Individual reconciled items

#### Inventory
- `products` - Products and services
- `product_categories` - Product categorization
- `product_inventory` - Inventory levels
- `inventory_locations` - Warehouse locations
- `inventory_movements` - Movement history

#### Additional
- `tax_rates` - Tax configurations
- `recurring_schedules` - Recurring transaction schedules
- `audit_logs` - Audit trail with hash chain
- `anomaly_detections` - Detected anomalies
- `theme_settings` - Per-company theme customization

## Architecture

### Directory Structure

```
app/
├── Filament/
│   ├── Clusters/           # Feature clusters (Sales, Expenses, Reporting)
│   ├── Pages/              # Custom pages (Dashboard, ThemeSettings)
│   ├── Resources/          # CRUD resources
│   └── Widgets/            # Dashboard widgets
├── Http/
│   └── Controllers/        # HTTP controllers
├── Livewire/               # Livewire components
├── Models/
│   └── Concerns/           # Model traits (HasUuid, BelongsToCompany)
├── Providers/
│   └── Filament/           # Panel providers
└── Services/               # Business logic services
```

### Model Concerns (Traits)

- **HasUuid** - UUID primary key support for all tenant-scoped models
- **BelongsToCompany** - Automatic company scoping
- **HasTripleEntry** - Triple-entry bookkeeping support

### Filament Resources

| Resource | Navigation Group | Features |
|----------|-----------------|----------|
| AccountResource | Accounting | Chart of accounts management |
| ContactResource | Sales | Customer/vendor management |
| ProductResource | Sales | Product/service management |
| InvoiceResource | Sales (Cluster) | Invoice CRUD |
| BillResource | Expenses (Cluster) | Bill CRUD |
| BankAccountResource | Banking | Bank account management |
| UserResource | Administration | User management (super admin only) |

### Livewire Components

| Component | Route | Purpose |
|-----------|-------|---------|
| InteractiveInvoiceBuilder | /sales/invoice-builder/{id?} | Dynamic invoice creation |
| BankReconciliation | /banking/reconcile/{id?} | Bank reconciliation interface |
| ImpersonationBanner | N/A (render hook) | Impersonation indicator |
| DynamicThemeStyles | N/A (render hook) | Dynamic CSS injection |

## Usage Guide

### Default Accounts

After seeding, access the admin panel at `/admin`:

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Super Admin |
| test@example.com | password | Regular User |

### Creating a Company

1. Log in with any account
2. Click "Register Company" or navigate to `/admin/new`
3. Fill in company details
4. You'll be redirected to the company dashboard

### Managing Chart of Accounts

1. Navigate to **Accounting > Accounts**
2. Click "New Account" to create accounts
3. Select account type and subtype
4. Set parent account for hierarchical structure

### Creating Invoices

1. Navigate to **Sales > Invoices**
2. Click "New Invoice"
3. Select customer, add line items
4. Save as draft or mark as sent

### Bank Reconciliation

1. Navigate to **Banking > Bank Accounts**
2. Select a bank account
3. Click "Reconcile" action
4. Match transactions and complete reconciliation

## Multi-Tenancy

This application uses Filament's built-in tenant system with the `Company` model.

### How It Works

- Each user can belong to multiple companies
- Super admins can access all companies
- Data is automatically scoped to the current tenant
- Resources use `BelongsToCompany` trait for automatic scoping

### Switching Companies

Click the company switcher in the sidebar to switch between companies.

### Super Admin Access

Super admins can:
- Access all companies without explicit membership
- Impersonate other users
- Manage system-wide settings

## Theme Customization

### Per-Company Themes

1. Navigate to **Settings > Theme Settings**
2. Choose a preset or customize individual colors
3. Modify brand name
4. Save settings

### Available Presets

| Preset | Description |
|--------|-------------|
| Slate | Dark blue-gray sidebar (default) |
| Dark | Pure dark theme |
| Indigo | Deep purple theme |
| Emerald | Green theme |
| Rose | Pink/red theme |
| Amber | Orange/yellow theme |
| Cyan | Teal theme |
| Light | Light sidebar theme |

### Customizable Colors

- Sidebar background
- Brand area background
- Text colors (primary and muted)
- Hover and active states
- Border colors
- Accent color (for active indicators)

## API Reference

### Web Routes

```
GET  /                                    → Redirect to dashboard
GET  /admin                               → Admin panel
GET  /admin/{tenant}                      → Tenant dashboard
GET  /admin/{tenant}/accounts             → Chart of accounts
GET  /admin/{tenant}/contacts             → Contacts list
GET  /admin/{tenant}/products             → Products list
GET  /admin/{tenant}/invoices             → Invoices list
GET  /admin/{tenant}/bills                → Bills list
GET  /admin/{tenant}/theme-settings       → Theme customization
```

### Livewire Routes

```
GET  /banking/reconcile/{bankAccountId?}  → Bank reconciliation
GET  /sales/invoice-builder/{invoiceId?}  → Invoice builder
```

### Impersonation Routes

```
GET  /impersonate/{user}                  → Start impersonation
GET  /stop-impersonating                  → Stop impersonation
```

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/InvoiceTest.php
```

### Creating Test Data

```bash
# Run seeders
php artisan db:seed

# Create sample data
php artisan db:seed --class=SampleDataSeeder
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Style

This project uses Laravel Pint for code formatting:

```bash
./vendor/bin/pint
```

## Project Statistics

| Category | Count |
|----------|-------|
| Models | 57 |
| Database Migrations | 32 |
| Filament Resources | 9 |
| Filament Clusters | 3 |
| Filament Widgets | 5 |
| Livewire Components | 5 |
| Services | 1 |

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## Support

For issues and feature requests, please use the GitHub Issues page.

## Acknowledgments

- [Laravel](https://laravel.com) - The PHP Framework
- [Filament](https://filamentphp.com) - Admin Panel
- [Livewire](https://livewire.laravel.com) - Full-stack Framework
- [Tailwind CSS](https://tailwindcss.com) - CSS Framework
- [Spatie](https://spatie.be) - Laravel Packages
