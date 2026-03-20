<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\MetafieldResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\MetafieldResource;

class CreateMetafield extends CreateRecord
{
    protected static string $resource = MetafieldResource::class;
}
