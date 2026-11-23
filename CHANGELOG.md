# Changelog

All notable changes to `laravel-shopify-sdk` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [2.1.0] - 2026-07-16

### Fixed
- **Permission gating**: the DraftOrder, Fulfillment, Discount and Metafield resources now use `HasShopifyPermissions`, so they are hidden from users without `draft_orders.view`, `fulfillments.view`, `discounts.view` or `metafields.view`. Before this, every user could see them.
- **Analytics page**: now checks `analytics.view` through `canAccess()`.

### Changed
- `filament/filament` added to `require-dev` so resource and page gating is covered by the test suite, plus a Filament-free trait matrix test.

Backward compatible: a User model without `hasShopifyPermission()` keeps full access, as with the other eleven resources.

## [2.0.0] - 2026-06-12

A release focused on living alongside an admin panel that already has its own Order, Product, Customer or User resources.

### Added
- `shopify.filament.slug_prefix` config (default `'shopify'`) and a `HasShopifySlug` trait on all 15 Filament resources.
- `HasShopifyLabels` trait: singular, plural and navigation labels come from the package translations (`en`, `ka`, `ru`).
- Config-driven panel: per-key switches for `shopify.filament.resources`, `pages` and `widgets`, and a `navigation_group` override.
- `LaravelShopifySdk\Filament\ShopifyPanel::registerOn($panel)` for one-line registration in an `AdminPanelProvider`.

### Changed
- **Breaking:** SDK resources move from `/admin/orders`, `/admin/products` and so on to `/admin/shopify/orders`, `/admin/shopify/products`. Set `slug_prefix => null` to keep the v1 URLs.
- **Breaking:** the User, Role and Permission resources are off by default.
- Hard-coded English `$navigationLabel` strings removed from all resources.

### Upgrading from 1.x
1. `composer require giorgigrdzelidze/laravel-shopify-sdk:^2.0`
2. `php artisan vendor:publish --tag=shopify-config --force`
3. Replace manual resource registration with `ShopifyPanel::registerOn($panel)`.

## [1.7.1] - 2026-06-12

### Fixed
- DraftOrder, Fulfillment, Discount and Metafield resources now declare their navigation group as the `$navigationGroup` static property instead of a `getNavigationGroup()` method, so apps can override it. Defaults are unchanged.

## [1.7.0] - 2026-06-12

### Added
- Order sync now stores the full practical Admin API order: customer, shipping and billing addresses, shipping line, money breakdown (subtotal, shipping, tax, discounts, refunded, outstanding), tax lines, discount applications, fulfillments and refunds.
- Line items gain variant title, vendor, quantities, discounted and original totals, image, tax lines and discount allocations.

No schema change: new fields land in the existing `payload` JSON columns. Validated against Admin API 2026-01.

## [1.6.0] - 2026-06-09

### Changed
- Laravel 13 support: `laravel/framework` and the `illuminate/*` packages now accept `^12.0 || ^13.0`. No source changes were needed.

## [1.5.0] - 2026-04-22

### Security
- OAuth callback validates a session-based `state` parameter (CSRF protection).
- Shop domains are checked against a strict pattern after normalisation.
- GraphQL errors now throw `ShopifyApiException` instead of being swallowed, and are no longer retried.

### Added
- `WebhookHandlerInterface` and a config-based handler registry; `AppUninstalledHandler` ships as the first handler.
- `SyncCompleted` and `SyncFailed` events from `SyncRunner`.
- `shopify:webhooks:retry` command (`--store`, `--topic`, `--limit`, `--dry-run`).
- Test suite grows from 23 to 75 tests covering the GraphQL and REST clients, OAuth, webhooks, sync events and the retry command.

### Fixed
- REST pagination via the `Link` header (it previously returned `null`).
- API version fallback corrected from `2024-01` to `2026-04` in both clients.
- SQLite-compatible column changes in migrations.

### Changed
- Widget chart queries use one `GROUP BY` instead of a query per day.
- Redesigned Analytics page and Top Products widget.

## [1.4.0] - 2026-03-20

### Added
- Product Types and Product Tags with full CRUD and Shopify sync, including "Assign to Products", "Rename on Shopify" and "Sync from Products" actions.
- README: Artisan command reference and a five-minute setup guide.

## [1.3.0] - 2026-03-20

### Added
- Metafields: sync, push, update and delete for products, collections and customers.
- Discounts: sync and push discount codes and automatic discounts.
- Draft orders: sync, create, push, complete and send invoices.
- Fulfillments: sync fulfillments, fulfillment orders and tracking events.
- Commands: `shopify:sync:discounts`, `shopify:sync:draft-orders`, `shopify:sync:fulfillments`.

### Fixed
- GraphQL client sent an empty `variables` parameter on some mutations.

## [1.2.0] - 2026-03-20

### Added
- Collections: smart and custom collection sync, full CRUD, image upload and diff-based product sync back to Shopify.
- Product images: featured image and full image list synced from GraphQL.
- Roles and permissions: five default roles, 27 permissions, `HasShopifyRoles` trait and `shopify:assign-admin` command.

## [1.1.0] - 2026-03-19

### Added
- `shopify:setup` command, including the OAuth2 client credentials grant for 2026+ apps.
- Sync buttons on the Products, Orders and Customers pages, and a filtered order summary bar.

### Fixed
- Inventory sync uses the 2026 `quantities` field instead of the deprecated `available`; adds `on_hand` to `inventory_levels`.
- Variant inventory display, InventoryAlert and SyncHealth widgets, and empty product descriptions in the product view.

## [1.0.0] - 2026-03-19

First release: OAuth and token modes, GraphQL client with cursor pagination, rate limiting and retries, HMAC-verified webhooks, encrypted token storage, sync runner, Filament v5 resources and widgets, and English, Georgian and Russian translations.

[2.1.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.7.1...v2.0.0
[1.7.1]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.7.0...v1.7.1
[1.7.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.5.0...v1.6.0
[1.5.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/GiorgiGrdzelidze/laravel-shopify-sdk/releases/tag/v1.0.0
