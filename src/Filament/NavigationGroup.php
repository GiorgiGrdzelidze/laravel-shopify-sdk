<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament;

enum NavigationGroup: string
{
    case Shopify = 'Shopify';
    case Marketing = 'Marketing';
    case Operations = 'Operations';
    case Reports = 'Reports';
    case AccessControl = 'Access Control';
}
