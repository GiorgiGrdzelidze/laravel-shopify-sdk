<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use LaravelShopifySdk\Filament\Resources\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
}
