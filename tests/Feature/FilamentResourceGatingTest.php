<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as Authenticatable;
use LaravelShopifySdk\Filament\Pages\Analytics;
use LaravelShopifySdk\Filament\Resources\CollectionResource;
use LaravelShopifySdk\Filament\Resources\DiscountResource;
use LaravelShopifySdk\Filament\Resources\DraftOrderResource;
use LaravelShopifySdk\Filament\Resources\FulfillmentResource;
use LaravelShopifySdk\Filament\Resources\MetafieldResource;
use LaravelShopifySdk\Filament\Resources\OrderResource;

class GatingUser extends Authenticatable
{
    /** @var array<int, string> */
    public array $granted = [];

    public function hasShopifyPermission(string $permission): bool
    {
        return in_array($permission, $this->granted, true);
    }
}

class GatingLegacyUser extends Authenticatable
{
}

/**
 * Every resource that previously lacked permission gating must now enforce it,
 * alongside the ones that already had it.
 *
 * @return array<string, array{class-string, string}>
 */
dataset('gated resources', [
    'discount' => [DiscountResource::class, 'discounts'],
    'draft order' => [DraftOrderResource::class, 'draft_orders'],
    'fulfillment' => [FulfillmentResource::class, 'fulfillments'],
    'metafield' => [MetafieldResource::class, 'metafields'],
    // Regression guard: resources that were already gated stay gated.
    'collection' => [CollectionResource::class, 'collections'],
    'order' => [OrderResource::class, 'orders'],
]);

it('hides the resource from an authorised user without the grant', function (string $resource) {
    $this->actingAs(new GatingUser());

    expect($resource::canViewAny())->toBeFalse();
})->with('gated resources');

it('shows the resource once the matching view grant is present', function (string $resource, string $prefix) {
    $user = new GatingUser();
    $user->granted = [$prefix . '.view'];
    $this->actingAs($user);

    expect($resource::canViewAny())->toBeTrue();
})->with('gated resources');

it('keeps the resource visible for legacy users without the contract', function (string $resource) {
    $this->actingAs(new GatingLegacyUser());

    expect($resource::canViewAny())->toBeTrue();
})->with('gated resources');

it('hides the resource when nobody is authenticated', function (string $resource) {
    expect($resource::canViewAny())->toBeFalse();
})->with('gated resources');

it('gates the analytics page behind the analytics.view grant', function () {
    // No user.
    expect(Analytics::canAccess())->toBeFalse();

    // Authorised, no grant.
    $user = new GatingUser();
    $this->actingAs($user);
    expect(Analytics::canAccess())->toBeFalse();

    // Authorised, wrong grant.
    $user->granted = ['orders.view'];
    expect(Analytics::canAccess())->toBeFalse();

    // Authorised, correct grant.
    $user->granted = ['analytics.view'];
    expect(Analytics::canAccess())->toBeTrue();
});

it('keeps the analytics page visible for legacy users without the contract', function () {
    $this->actingAs(new GatingLegacyUser());

    expect(Analytics::canAccess())->toBeTrue();
});
