<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\DraftOrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\DraftOrderResource;

class CreateDraftOrder extends CreateRecord
{
    protected static string $resource = DraftOrderResource::class;
}
