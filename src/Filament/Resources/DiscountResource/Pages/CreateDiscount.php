<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\DiscountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\DiscountResource;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;
}
