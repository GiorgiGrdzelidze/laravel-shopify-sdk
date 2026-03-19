<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use LaravelShopifySdk\Filament\Resources\CustomerResource;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;
}
