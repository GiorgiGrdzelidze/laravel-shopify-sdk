<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;

/**
 * Resource double that uses the trait exactly as the real Filament resources do.
 */
class GatedResourceDouble
{
    use HasShopifyPermissions;

    protected static function getPermissionPrefix(): string
    {
        return 'discounts';
    }
}

/**
 * User that implements the optional hasShopifyPermission() contract.
 */
class PermissionAwareUser extends Authenticatable
{
    /** @var array<int, string> */
    public array $granted = [];

    public function hasShopifyPermission(string $permission): bool
    {
        return in_array($permission, $this->granted, true);
    }
}

/**
 * User that does NOT implement the contract (legacy consumer app).
 */
class LegacyUser extends Authenticatable
{
}

function dummyRecord(): Model
{
    return new class extends Model {};
}

it('denies every action when nobody is authenticated', function () {
    expect(GatedResourceDouble::canViewAny())->toBeFalse()
        ->and(GatedResourceDouble::canCreate())->toBeFalse()
        ->and(GatedResourceDouble::canDeleteAny())->toBeFalse()
        ->and(GatedResourceDouble::canView(dummyRecord()))->toBeFalse()
        ->and(GatedResourceDouble::canEdit(dummyRecord()))->toBeFalse()
        ->and(GatedResourceDouble::canDelete(dummyRecord()))->toBeFalse();
});

it('allows every action for a user without the contract (backward compatibility)', function () {
    $this->actingAs(new LegacyUser());

    expect(GatedResourceDouble::canViewAny())->toBeTrue()
        ->and(GatedResourceDouble::canCreate())->toBeTrue()
        ->and(GatedResourceDouble::canDeleteAny())->toBeTrue()
        ->and(GatedResourceDouble::canView(dummyRecord()))->toBeTrue()
        ->and(GatedResourceDouble::canEdit(dummyRecord()))->toBeTrue()
        ->and(GatedResourceDouble::canDelete(dummyRecord()))->toBeTrue();
});

it('denies every action for an authorised user with no grants', function () {
    $this->actingAs(new PermissionAwareUser());

    expect(GatedResourceDouble::canViewAny())->toBeFalse()
        ->and(GatedResourceDouble::canCreate())->toBeFalse()
        ->and(GatedResourceDouble::canDeleteAny())->toBeFalse()
        ->and(GatedResourceDouble::canView(dummyRecord()))->toBeFalse()
        ->and(GatedResourceDouble::canEdit(dummyRecord()))->toBeFalse()
        ->and(GatedResourceDouble::canDelete(dummyRecord()))->toBeFalse();
});

it('maps each action to its own prefixed permission', function () {
    $user = new PermissionAwareUser();
    $this->actingAs($user);

    $user->granted = ['discounts.view'];
    expect(GatedResourceDouble::canViewAny())->toBeTrue()
        ->and(GatedResourceDouble::canView(dummyRecord()))->toBeTrue()
        ->and(GatedResourceDouble::canCreate())->toBeFalse()
        ->and(GatedResourceDouble::canEdit(dummyRecord()))->toBeFalse()
        ->and(GatedResourceDouble::canDelete(dummyRecord()))->toBeFalse();

    $user->granted = ['discounts.create'];
    expect(GatedResourceDouble::canCreate())->toBeTrue()
        ->and(GatedResourceDouble::canViewAny())->toBeFalse();

    $user->granted = ['discounts.edit'];
    expect(GatedResourceDouble::canEdit(dummyRecord()))->toBeTrue()
        ->and(GatedResourceDouble::canViewAny())->toBeFalse();

    $user->granted = ['discounts.delete'];
    expect(GatedResourceDouble::canDelete(dummyRecord()))->toBeTrue()
        ->and(GatedResourceDouble::canDeleteAny())->toBeTrue()
        ->and(GatedResourceDouble::canViewAny())->toBeFalse();
});

it('does not leak grants across permission prefixes', function () {
    $user = new PermissionAwareUser();
    $user->granted = ['products.view', 'orders.view'];
    $this->actingAs($user);

    expect(GatedResourceDouble::canViewAny())->toBeFalse();
});
