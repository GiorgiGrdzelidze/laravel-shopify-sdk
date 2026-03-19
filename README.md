<div align="center">

# 🛍️ Laravel Shopify SDK

**Production-grade Laravel 12 package for Shopify Admin API integration**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.0%2B-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-v5-FDAE4B?style=flat-square&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTEyIDJMMiA3bDEwIDUgMTAtNS0xMC01ek0yIDE3bDEwIDUgMTAtNS0xMC01LTEwIDV6Ii8+PC9zdmc+)](https://filamentphp.com)
[![License](https://img.shields.io/badge/License-MIT-22C55E?style=flat-square)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-Passing-22C55E?style=flat-square)](tests/)

*OAuth • Webhooks • GraphQL & REST • Data Mirroring • Multi-Store • Filament v5*

</div>

---

## 📦 Package Identity

| Property | Value |
|----------|-------|
| **Composer Name** | `giorgigrdzelidze/laravel-shopify-sdk` |
| **PHP Namespace** | `LaravelShopifySdk\` |
| **Monorepo Path** | `packages/giorgigrdzelidze/laravel-shopify-sdk` |
| **GitHub** | https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk |

---

## ✨ Features

- 🔐 **OAuth Authentication** - Authorization Code Grant flow for multi-store installations
- 🪝 **Webhook Management** - Secure webhook verification and processing with HMAC-SHA256
- ⚡ **GraphQL & REST APIs** - Unified client with automatic rate limiting and retries
- 🔄 **Data Mirroring** - Sync products, orders, customers, and inventory to your database
- 🎯 **Artisan Commands** - CLI tools for manual and scheduled synchronization
- 🏪 **Multi-Store Support** - Manage multiple Shopify stores from a single application
- 🎛️ **Single-Store Mode** - Optional simplified setup for single-store applications
- 🧩 **Filament v5 Integration** - Optional admin panel resources and widgets (mobile-first)
- 📊 **Rate Limiting** - Intelligent throttling for both REST and GraphQL APIs
- 🧪 **Comprehensive Tests** - PHPUnit/Pest test suite included
- 🌍 **Multi-Language** - Support for EN, KA, RU
- 🔒 **Strict Types** - Full PHP strict typing for type safety

---

## 📋 Table of Contents

- [Requirements](#-requirements)
- [Quick Start](#-quick-start)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Filament v5 Integration](#-filament-v5-integration-optional)
- [Testing](#-testing)
- [Security](#-security)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)

---

## 📦 Requirements

| Requirement | Version |
|------------|--------|
| PHP | 8.2+ |
| Laravel | 12.0+ |
| Database | MySQL/PostgreSQL/SQLite |
| Filament (optional) | 5.0+ |

### Compatibility Matrix

✅ **Laravel 12** with PHP 8.2+  
✅ **Filament v5** (optional, not required)  
✅ **Shopify API** 2026-04 (configurable)

---

## 🚀 Quick Start

```bash
# 1. Install the package
composer require giorgigrdzelidze/laravel-shopify-sdk

# 2. Publish config and migrations
php artisan vendor:publish --tag=shopify-config
php artisan vendor:publish --tag=shopify-migrations

# 3. Run migrations
php artisan migrate

# 4. Configure your .env
SHOPIFY_CLIENT_ID=your-client-id
SHOPIFY_CLIENT_SECRET=your-client-secret
SHOPIFY_API_VERSION=2026-04

# 5. Start syncing!
php artisan shopify:sync:products
```

**That's it!** 🎉 You're ready to integrate with Shopify.

---

## 💾 Installation

Install the package via Composer:

```bash
composer require giorgigrdzelidze/laravel-shopify-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=shopify-config
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=shopify-migrations
php artisan migrate
```

Optionally publish translations:

```bash
php artisan vendor:publish --tag=shopify-lang
```

### 📚 Namespace & Autoloading

This package uses the root namespace `LaravelShopifySdk\` for all classes. After installing or updating the package, ensure you regenerate the autoloader:

```bash
composer dump-autoload
```

**Package Structure:**

| Namespace | Purpose |
|-----------|--------|
| `LaravelShopifySdk\Auth\*` | 🔐 Authentication and OAuth |
| `LaravelShopifySdk\Clients\*` | ⚡ API clients (GraphQL, REST) |
| `LaravelShopifySdk\Models\*` | 📦 Eloquent models |
| `LaravelShopifySdk\Sync\*` | 🔄 Sync engine |
| `LaravelShopifySdk\Commands\*` | 🎯 Artisan commands |
| `LaravelShopifySdk\Webhooks\*` | 🪝 Webhook handling |
| `LaravelShopifySdk\Exceptions\*` | ⚠️ Custom exceptions |

---

## ⚙️ Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Shopify API Version (2026-04 is the latest stable)
SHOPIFY_API_VERSION=2026-04

# Webhook Secret (required for webhooks)
SHOPIFY_WEBHOOK_SECRET=your-webhook-secret

# Optional: Filament Integration
SHOPIFY_FILAMENT_ENABLED=false
```

### Single Store Mode vs Multi-Store Mode

Choose one of the following configurations:

#### Option 1: Single Store Mode (Recommended for single store)

Use this if you're managing a single Shopify store. No database required for store credentials.

```env
# Enable Single Store Mode
SHOPIFY_SINGLE_STORE_ENABLED=true
SHOPIFY_SHOP_DOMAIN=your-store.myshopify.com
SHOPIFY_ACCESS_TOKEN=shpat_xxxxxxxxxxxxxxxxxxxxx
```

**How to get your access token:**
1. Go to your Shopify Admin
2. Settings → Apps and sales channels → Develop apps
3. Create a custom app with required scopes
4. Install the app and copy the Admin API access token

#### Option 2: Multi-Store Mode (For multiple stores with OAuth)

Use this if you're building a public app or managing multiple stores.

```env
# Disable Single Store Mode
SHOPIFY_SINGLE_STORE_ENABLED=false

# OAuth Credentials (required for multi-store)
SHOPIFY_CLIENT_ID=your-client-id
SHOPIFY_CLIENT_SECRET=your-client-secret
SHOPIFY_SCOPES=read_products,write_products,read_orders,write_orders,read_customers,write_customers,read_inventory,write_inventory
SHOPIFY_REDIRECT_URI=https://your-app.com/shopify/callback
```

**Important:** In multi-store mode, stores must be installed via OAuth flow before syncing. The package will store credentials in the `shopify_stores` table.

**To install a store manually in multi-store mode:**
```php
use LaravelShopifySdk\Auth\StoreRepository;

$repository = app(StoreRepository::class);
$repository->createOrUpdate(
    shopDomain: 'your-store.myshopify.com',
    accessToken: 'shpat_xxxxxxxxxxxxxxxxxxxxx',
    scopes: 'read_products,write_products,read_orders'
);
```

### Configuration File

The `config/shopify.php` file provides extensive customization options:

- API version and endpoints
- OAuth settings
- Route configuration
- Webhook handling
- Rate limiting parameters
- Sync behavior
- Filament integration
- Logging preferences

---

## 📖 Usage

### 🔐 OAuth Installation Flow

#### 1. Register Your App

Create a Shopify app in your Partner Dashboard and configure:
- **App URL**: `https://your-app.com`
- **Allowed redirection URL(s)**: `https://your-app.com/shopify/callback`

#### 2. Installation Route

Direct merchants to your install route:

```
https://your-app.com/shopify/install?shop=store-name.myshopify.com
```

The package handles the OAuth flow automatically:
1. Redirects to Shopify for authorization
2. Validates the callback HMAC
3. Exchanges code for access token
4. Stores encrypted credentials in database

#### 3. Post-Installation

After successful installation, the store is marked as active and ready for API calls.

### ⚡ Making API Calls

#### GraphQL API (Recommended)

```php
use LaravelShopifySdk\Clients\ShopifyClient;

$client = app(ShopifyClient::class);

// Get store instance
$store = $client->getStore('store-name.myshopify.com');

// Execute GraphQL query
$query = <<<GQL
{
  products(first: 10) {
    edges {
      node {
        id
        title
        status
      }
    }
  }
}
GQL;

$response = $client->graphql($store)->query($store, $query);
```

#### REST API

```php
// GET request
$products = $client->rest($store)->get($store, 'products.json', [
    'limit' => 50,
    'status' => 'active',
]);

// POST request
$product = $client->rest($store)->post($store, 'products.json', [
    'product' => [
        'title' => 'New Product',
        'vendor' => 'My Store',
    ],
]);

// PUT request
$updated = $client->rest($store)->put($store, 'products/123.json', [
    'product' => ['status' => 'draft'],
]);

// DELETE request
$client->rest($store)->delete($store, 'products/123.json');
```

### 🪝 Webhooks

#### Setup Webhooks in Shopify

Register webhooks in your Shopify admin or via API:

```
POST /admin/api/2026-04/webhooks.json
{
  "webhook": {
    "topic": "products/create",
    "address": "https://your-app.com/shopify/webhooks",
    "format": "json"
  }
}
```

#### Webhook Verification

Webhooks are automatically verified using HMAC-SHA256. Invalid webhooks are rejected with a 401 response.

#### Processing Webhooks

The package stores all webhook events in the `shopify_webhook_events` table for:
- Idempotency (prevents duplicate processing)
- Audit trail
- Retry capability

Webhooks are processed asynchronously by default. The `app/uninstalled` webhook automatically marks stores as inactive.

### 🔄 Data Synchronization

#### Manual Sync via Artisan

```bash
# Sync products
php artisan shopify:sync:products --store=store-name.myshopify.com

# Sync orders (with date range)
php artisan shopify:sync:orders --date-from=2026-01-01 --date-to=2026-03-16

# Sync customers (incremental since last sync)
php artisan shopify:sync:customers --since=2026-03-01

# Sync inventory
php artisan shopify:sync:inventory

# Sync all entities
php artisan shopify:sync:all

# Dry run (preview without syncing)
php artisan shopify:sync:products --dry-run

# Sync all active stores
php artisan shopify:sync:products
```

#### Scheduled Sync

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Sync products daily at 2 AM
    $schedule->command('shopify:sync:products')->dailyAt('02:00');
    
    // Sync orders every hour
    $schedule->command('shopify:sync:orders')->hourly();
    
    // Sync customers daily at 3 AM
    $schedule->command('shopify:sync:customers')->dailyAt('03:00');
    
    // Sync inventory every 15 minutes
    $schedule->command('shopify:sync:inventory')->everyFifteenMinutes();
}
```

#### Programmatic Sync

```php
use LaravelShopifySdk\Sync\SyncRunner;
use LaravelShopifySdk\Auth\StoreRepository;

$runner = app(SyncRunner::class);
$repository = app(StoreRepository::class);

$store = $repository->findByDomain('store-name.myshopify.com');

// Sync products
$syncRun = $runner->syncProducts($store, [
    'since' => '2026-03-01',
]);

echo "Synced {$syncRun->counts['products']} products in {$syncRun->duration_ms}ms";

// Sync orders with date range
$syncRun = $runner->syncOrders($store, [
    'date_from' => '2026-01-01',
    'date_to' => '2026-03-16',
]);
```

### 📦 Data Models

All synced data is stored with:
- **Full JSON payload** in `payload` column
- **Searchable columns** for common queries
- **Relationships** between entities

```php
use LaravelShopifySdk\Models\Product;
use LaravelShopifySdk\Models\Order;
use LaravelShopifySdk\Models\Customer;

// Query products
$products = Product::where('store_id', $store->id)
    ->where('status', 'active')
    ->where('vendor', 'Nike')
    ->get();

// Access full payload
$productData = $product->payload;

// Relationships
$variants = $product->variants;
$store = $product->store;

// Query orders
$orders = Order::where('store_id', $store->id)
    ->where('financial_status', 'paid')
    ->whereBetween('processed_at', [$from, $to])
    ->with('lineItems')
    ->get();
```

---

## 📊 Rate Limiting

The package automatically handles rate limiting for both REST and GraphQL APIs:

### REST API
- Bucket-based rate limiting (40 requests per second)
- Automatic throttling when approaching limits
- Respects `X-Shopify-Shop-Api-Call-Limit` header

### GraphQL API
- Cost-based throttling (1000 points maximum)
- Monitors `extensions.cost` in responses
- Throttles when available points drop below threshold
- Restores at 50 points per second

### Retry Logic
- Automatic retries on 429 (rate limit) responses
- Exponential backoff for 5xx server errors
- Configurable retry attempts and delays

---

## 🔖 API Versioning

Shopify guarantees stable API versions for **minimum 12 months**. The package defaults to `2026-04`.

### Upgrading API Versions

1. Update `SHOPIFY_API_VERSION` in `.env`
2. Review [Shopify's changelog](https://shopify.dev/docs/api/release-notes)
3. Test thoroughly in development
4. Deploy to production

The package supports per-store API versions if needed.

---

## 🧩 Filament v5 Integration (Optional)

> **Note:** Filament is **completely optional** and not a hard dependency. Install only if you need admin panel features.

### Installation

```bash
composer require filament/filament:"^5.0"
```

### Enable in Config

```php
// config/shopify.php
'filament' => [
    'enabled' => true,
],
```

### ✨ Features

- 📱 **Mobile-first design** - Responsive tables and forms
- 📋 **Resources** - Stores, Products, Variants, Orders, Customers, Locations, Inventory, Webhooks, Sync Runs
- 📊 **Widgets** - Sync health dashboard, order statistics, product counts
- ⚡ **Actions** - Manual sync triggers, view JSON payloads
- 🔍 **Filters** - Store, status, date ranges
- ⚡ **Cached queries** - Widget data cached for 5 minutes
- 🎨 **Auto-discovery** - Resources and widgets auto-register when enabled

### Filament v5 Compatibility

This package is built for **Filament v5** with:
- Modern panel API
- Auto-discovery support
- Mobile-first responsive design
- Enhanced widget system

### 🏪 Stores CRUD Management

Full CRUD operations for managing Shopify stores directly from Filament:

**Features:**
- **Create stores** manually with token mode (shop_domain, access_token, scopes)
- **Edit stores** - rotate tokens, update scopes, activate/deactivate
- **Delete stores** - with confirmation and cascade delete
- **Test Connection** - verify store credentials with a lightweight API call
- **Sync Actions** - trigger sync for products, orders, customers, or all data

**Token Security:**
- Access tokens are encrypted at rest using Laravel's encryption
- Tokens are hidden in forms with reveal option
- Masked token display in views (e.g., `shpa••••••cdef`)

**Store Modes:**
- `oauth` - Store installed via Shopify OAuth flow
- `token` - Store added manually with access token

### 🧪 Sandbox CRUD Mode (Testing)

For testing UI flows, filtering, and relationships, enable Sandbox CRUD mode:

```env
SHOPIFY_TESTING_CRUD_ENABLED=true
```

Or in config:

```php
// config/shopify.php
'filament' => [
    'testing_crud_enabled' => env('SHOPIFY_TESTING_CRUD_ENABLED', false),
],
```

**When enabled:**
- Create/Edit/Delete actions appear for Products, Orders, Customers
- ⚠️ **Sandbox Mode** warning badge displayed in forms
- JSON payload editor available for raw data manipulation
- Changes are **LOCAL ONLY** - they do NOT sync back to Shopify

**Use cases:**
- Testing Filament UI components
- Developing custom filters and actions
- Populating test data for demos
- Debugging relationship queries

### 📊 Sync Health Widget

Monitor sync status across all stores and entities:

| Column | Description |
|--------|-------------|
| Store | Shop domain |
| Entity | products, orders, customers, inventory |
| Status | running, completed, failed |
| Records | Count of synced items |
| Duration | Time taken (ms/s/m) |
| Errors | Error count or ✓ |
| Last Sync | Timestamp with relative time |

The widget shows the **latest sync run per entity per store** and auto-refreshes every 60 seconds.

---

## 🧪 Testing

Run the test suite:

```bash
cd packages/giorgigrdzelidze/laravel-shopify-sdk
composer install
vendor/bin/pest
```

Or with PHPUnit:

```bash
vendor/bin/phpunit
```

### Test Coverage

The package includes tests for:
- HMAC validation (OAuth and webhooks)
- Store repository operations
- Token encryption
- OAuth callback verification
- Rate limiting behavior

---

## 🔒 Security

### Credentials Storage
- Access tokens are encrypted at rest using Laravel's encryption
- Never log or expose access tokens
- Webhook secrets are stored in configuration

### HMAC Verification
- OAuth callbacks verified with timing-safe comparison
- Webhooks verified using `X-Shopify-Hmac-SHA256` header
- Raw request body used for webhook verification

### Best Practices
- Use HTTPS for all endpoints
- Rotate webhook secrets periodically
- Monitor failed webhook verifications
- Implement IP whitelisting if needed

---

## 🔧 Troubleshooting

### OAuth Installation Fails

**Issue**: "Invalid HMAC signature"

**Solution**: 
- Verify `SHOPIFY_CLIENT_SECRET` matches your app settings
- Ensure callback URL is exactly as configured in Shopify
- Check for URL encoding issues

### Webhook Verification Fails

**Issue**: Webhooks return 401

**Solution**:
- Verify `SHOPIFY_WEBHOOK_SECRET` is correct
- Ensure raw request body is used (disable middleware that reads body)
- Check webhook is registered with correct URL

### Rate Limit Errors

**Issue**: 429 responses despite throttling

**Solution**:
- Reduce `SHOPIFY_SYNC_CHUNK_SIZE`
- Increase delays between sync runs
- Use incremental syncs (`--since` flag)
- Sync during off-peak hours

### Sync Performance

**Issue**: Syncs are slow

**Solution**:
- Use GraphQL instead of REST where possible
- Enable queue processing (`--queue` flag)
- Optimize chunk sizes
- Add database indexes for common queries

---

## 🧑‍💻 How to Test with Your Credentials

### 1. Setup Environment

```bash
cp .env.example .env
```

Add your Shopify app credentials:

```env
SHOPIFY_CLIENT_ID=your-actual-client-id
SHOPIFY_CLIENT_SECRET=your-actual-client-secret
SHOPIFY_WEBHOOK_SECRET=your-actual-webhook-secret
```

### 2. Install a Test Store

Visit:
```
http://localhost:8000/shopify/install?shop=your-dev-store.myshopify.com
```

### 3. Test Webhooks Locally

Use ngrok to expose your local server:

```bash
ngrok http 8000
```

Register webhook with ngrok URL:
```
https://your-ngrok-url.ngrok.io/shopify/webhooks
```

Send test webhook from Shopify admin or use:

```bash
curl -X POST http://localhost:8000/shopify/webhooks \
  -H "X-Shopify-Topic: products/create" \
  -H "X-Shopify-Shop-Domain: your-store.myshopify.com" \
  -H "X-Shopify-Hmac-SHA256: $(echo -n '{"id":123}' | openssl dgst -sha256 -hmac 'your-webhook-secret' -binary | base64)" \
  -d '{"id":123}'
```

### 4. Test Sync Commands

```bash
# Sync products from your test store
php artisan shopify:sync:products --store=your-dev-store.myshopify.com

# View sync results
php artisan shopify:sync:stores
```

---

## 🤝 Contributing

Contributions are welcome! Please ensure:
- All tests pass
- Code follows PSR-12 standards
- PHPDoc is complete
- README is updated for new features

---

## 📄 License

MIT License. See LICENSE file for details.

---

## 💬 Support

For issues, questions, or feature requests, please open an issue on GitHub.

---

## 📝 Changelog

### 1.0.0 (2026-03-16)

**Initial Release** 🎉

- ✅ OAuth Authorization Code Grant flow
- ✅ Webhook verification and processing
- ✅ GraphQL and REST API clients
- ✅ Rate limiting and retry logic
- ✅ Data mirroring for products, orders, customers, inventory
- ✅ Artisan sync commands
- ✅ Optional Filament v5 integration
- ✅ Comprehensive test suite
- ✅ Multi-language support (EN, KA, RU)
- ✅ Strict types across entire codebase
- ✅ Complete PHPDoc coverage

---

## 🌟 Credits

Built with ❤️ for the Laravel and Shopify communities.

**Powered by:**
- [Laravel 12](https://laravel.com)
- [Shopify Admin API](https://shopify.dev/docs/api/admin)
- [Filament v5](https://filamentphp.com) (optional)
