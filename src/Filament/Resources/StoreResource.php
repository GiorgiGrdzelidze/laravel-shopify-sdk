<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\NavigationIcon;
use LaravelShopifySdk\Filament\Resources\StoreResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Sync\SyncRunner;
use Illuminate\Support\Facades\Http;
use BackedEnum;

class StoreResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = Store::class;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedBuildingStorefront;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static ?int $navigationSort = 1;

    protected static function getPermissionPrefix(): string
    {
        return 'stores';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Store Details')
                    ->description('Configure your Shopify store connection')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Select::make('status')
                            ->options([
                                Store::STATUS_ACTIVE => 'Active',
                                Store::STATUS_INACTIVE => 'Inactive',
                                Store::STATUS_UNINSTALLED => 'Uninstalled',
                            ])
                            ->default(Store::STATUS_ACTIVE)
                            ->required()
                            ->native(false)
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        Select::make('currency')
                            ->label('Currency')
                            ->options(self::getAllCurrencies())
                            ->default('USD')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        TextInput::make('shop_domain')
                            ->label('Shopify Domain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('your-store.myshopify.com')
                            ->helperText('The Shopify admin domain')
                            ->prefixIcon('heroicon-o-server')
                            ->rules(['regex:/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/'])
                            ->validationMessages([
                                'regex' => 'Must be a valid Shopify domain (e.g., your-store.myshopify.com)',
                            ])
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        TextInput::make('custom_domain')
                            ->label('Website Domain')
                            ->maxLength(255)
                            ->placeholder('https://your-store.com')
                            ->helperText('Your live website URL for product links')
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->url()
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        Select::make('mode')
                            ->label('Connection Mode')
                            ->options([
                                Store::MODE_OAUTH => 'OAuth (App Installation)',
                                Store::MODE_TOKEN => 'Token (Manual)',
                            ])
                            ->default(Store::MODE_TOKEN)
                            ->required()
                            ->native(false)
                            ->helperText('OAuth: App installed via Shopify. Token: Manual access token entry.')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('Authentication')
                    ->description('Secure API credentials for store access')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('access_token')
                            ->label('Access Token')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Leave empty to keep current token. Enter new token to update.'
                                : 'The Shopify Admin API access token. This will be encrypted at rest.')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => $state)
                            ->columnSpanFull(),
                        Textarea::make('scopes')
                            ->label('API Scopes')
                            ->placeholder('read_products,write_products,read_orders,write_orders')
                            ->helperText('Comma-separated list of granted API scopes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Additional Settings')
                    ->description('Optional metadata and configuration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Custom Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Field')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-globe-alt')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('mode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Store::MODE_OAUTH => 'OAuth',
                        Store::MODE_TOKEN => 'Token',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Store::MODE_OAUTH => 'info',
                        Store::MODE_TOKEN => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        Store::STATUS_ACTIVE => 'success',
                        Store::STATUS_INACTIVE => 'warning',
                        Store::STATUS_UNINSTALLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('scopes')
                    ->label('Scopes')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '0 scopes';
                        }
                        $scopesArray = array_filter(explode(',', $state));
                        $count = count($scopesArray);
                        return $count . ' scope' . ($count !== 1 ? 's' : '');
                    })
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Orders')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('installed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Store::STATUS_ACTIVE => 'Active',
                        Store::STATUS_INACTIVE => 'Inactive',
                        Store::STATUS_UNINSTALLED => 'Uninstalled',
                    ]),
                Tables\Filters\SelectFilter::make('mode')
                    ->options([
                        Store::MODE_OAUTH => 'OAuth',
                        Store::MODE_TOKEN => 'Token',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('test_connection')
                        ->label('Test Connection')
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Test Store Connection')
                        ->modalDescription('This will make a lightweight API call to verify the store credentials.')
                        ->action(function (Store $record) {
                            try {
                                $url = "https://{$record->shop_domain}/admin/api/" . config('shopify.api_version', '2024-01') . "/shop.json";

                                $response = Http::withHeaders([
                                    'X-Shopify-Access-Token' => $record->access_token,
                                ])
                                ->timeout(10)
                                ->get($url);

                                if ($response->successful()) {
                                    $shopData = $response->json('shop');
                                    Notification::make()
                                        ->title('Connection Successful')
                                        ->body("Connected to: {$shopData['name']} ({$shopData['email']})")
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Connection Failed')
                                        ->body("HTTP {$response->status()}: Unable to connect to store.")
                                        ->danger()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Connection Error')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('sync_all')
                        ->label('Sync All Data')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Sync All Data')
                        ->modalDescription('This will sync products, orders, customers, and inventory from Shopify. This may take several minutes.')
                        ->action(function (Store $record) {
                            try {
                                $runner = app(SyncRunner::class);

                                $runner->syncProducts($record);
                                $runner->syncOrders($record);
                                $runner->syncCustomers($record);
                                $runner->syncInventory($record);

                                Notification::make()
                                    ->title('Sync Complete')
                                    ->body('All data has been synced from Shopify.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Sync Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('sync_products')
                        ->label('Sync Products')
                        ->icon('heroicon-o-cube')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Products')
                        ->modalDescription('This will sync all products from Shopify. Continue?')
                        ->action(function (Store $record) {
                            try {
                                $runner = app(SyncRunner::class);
                                $result = $runner->syncProducts($record);

                                Notification::make()
                                    ->title('Products Synced')
                                    ->body("Synced {$result->counts['products']} products, {$result->counts['variants']} variants.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Sync Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('sync_orders')
                        ->label('Sync Orders')
                        ->icon('heroicon-o-shopping-cart')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Orders')
                        ->modalDescription('This will sync all orders from Shopify. Continue?')
                        ->action(function (Store $record) {
                            try {
                                $runner = app(SyncRunner::class);
                                $result = $runner->syncOrders($record);

                                Notification::make()
                                    ->title('Orders Synced')
                                    ->body("Synced {$result->counts['orders']} orders, {$result->counts['line_items']} line items.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Sync Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('sync_customers')
                        ->label('Sync Customers')
                        ->icon('heroicon-o-users')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Customers')
                        ->modalDescription('This will sync all customers from Shopify. Continue?')
                        ->action(function (Store $record) {
                            try {
                                $runner = app(SyncRunner::class);
                                $result = $runner->syncCustomers($record);

                                Notification::make()
                                    ->title('Customers Synced')
                                    ->body("Synced {$result->counts['customers']} customers.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Sync Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Store $record) => $record->status === Store::STATUS_ACTIVE)
                        ->action(function (Store $record) {
                            $record->markAsInactive();
                            Notification::make()
                                ->title('Store Deactivated')
                                ->success()
                                ->send();
                        }),
                    Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (Store $record) => $record->status !== Store::STATUS_ACTIVE)
                        ->action(function (Store $record) {
                            $record->markAsActive();
                            Notification::make()
                                ->title('Store Activated')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Store')
                        ->modalDescription('Are you sure you want to delete this store? This will also delete all associated products, orders, and customers. This action cannot be undone.'),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \LaravelShopifySdk\Filament\Resources\StoreResource\RelationManagers\LocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'view' => Pages\ViewStore::route('/{record}'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }

    protected static function getAllCurrencies(): array
    {
        return [
            'AED' => 'AED - UAE Dirham',
            'AFN' => 'AFN - Afghan Afghani',
            'ALL' => 'ALL - Albanian Lek',
            'AMD' => 'AMD - Armenian Dram',
            'ANG' => 'ANG - Netherlands Antillean Guilder',
            'AOA' => 'AOA - Angolan Kwanza',
            'ARS' => 'ARS - Argentine Peso',
            'AUD' => 'AUD - Australian Dollar',
            'AWG' => 'AWG - Aruban Florin',
            'AZN' => 'AZN - Azerbaijani Manat',
            'BAM' => 'BAM - Bosnia-Herzegovina Convertible Mark',
            'BBD' => 'BBD - Barbadian Dollar',
            'BDT' => 'BDT - Bangladeshi Taka',
            'BGN' => 'BGN - Bulgarian Lev',
            'BHD' => 'BHD - Bahraini Dinar',
            'BIF' => 'BIF - Burundian Franc',
            'BMD' => 'BMD - Bermudan Dollar',
            'BND' => 'BND - Brunei Dollar',
            'BOB' => 'BOB - Bolivian Boliviano',
            'BRL' => 'BRL - Brazilian Real',
            'BSD' => 'BSD - Bahamian Dollar',
            'BTN' => 'BTN - Bhutanese Ngultrum',
            'BWP' => 'BWP - Botswanan Pula',
            'BYN' => 'BYN - Belarusian Ruble',
            'BZD' => 'BZD - Belize Dollar',
            'CAD' => 'CAD - Canadian Dollar',
            'CDF' => 'CDF - Congolese Franc',
            'CHF' => 'CHF - Swiss Franc',
            'CLP' => 'CLP - Chilean Peso',
            'CNY' => 'CNY - Chinese Yuan',
            'COP' => 'COP - Colombian Peso',
            'CRC' => 'CRC - Costa Rican Colón',
            'CUP' => 'CUP - Cuban Peso',
            'CVE' => 'CVE - Cape Verdean Escudo',
            'CZK' => 'CZK - Czech Koruna',
            'DJF' => 'DJF - Djiboutian Franc',
            'DKK' => 'DKK - Danish Krone',
            'DOP' => 'DOP - Dominican Peso',
            'DZD' => 'DZD - Algerian Dinar',
            'EGP' => 'EGP - Egyptian Pound',
            'ERN' => 'ERN - Eritrean Nakfa',
            'ETB' => 'ETB - Ethiopian Birr',
            'EUR' => 'EUR - Euro',
            'FJD' => 'FJD - Fijian Dollar',
            'FKP' => 'FKP - Falkland Islands Pound',
            'GBP' => 'GBP - British Pound',
            'GEL' => 'GEL - Georgian Lari',
            'GHS' => 'GHS - Ghanaian Cedi',
            'GIP' => 'GIP - Gibraltar Pound',
            'GMD' => 'GMD - Gambian Dalasi',
            'GNF' => 'GNF - Guinean Franc',
            'GTQ' => 'GTQ - Guatemalan Quetzal',
            'GYD' => 'GYD - Guyanaese Dollar',
            'HKD' => 'HKD - Hong Kong Dollar',
            'HNL' => 'HNL - Honduran Lempira',
            'HRK' => 'HRK - Croatian Kuna',
            'HTG' => 'HTG - Haitian Gourde',
            'HUF' => 'HUF - Hungarian Forint',
            'IDR' => 'IDR - Indonesian Rupiah',
            'ILS' => 'ILS - Israeli New Shekel',
            'INR' => 'INR - Indian Rupee',
            'IQD' => 'IQD - Iraqi Dinar',
            'IRR' => 'IRR - Iranian Rial',
            'ISK' => 'ISK - Icelandic Króna',
            'JMD' => 'JMD - Jamaican Dollar',
            'JOD' => 'JOD - Jordanian Dinar',
            'JPY' => 'JPY - Japanese Yen',
            'KES' => 'KES - Kenyan Shilling',
            'KGS' => 'KGS - Kyrgystani Som',
            'KHR' => 'KHR - Cambodian Riel',
            'KMF' => 'KMF - Comorian Franc',
            'KPW' => 'KPW - North Korean Won',
            'KRW' => 'KRW - South Korean Won',
            'KWD' => 'KWD - Kuwaiti Dinar',
            'KYD' => 'KYD - Cayman Islands Dollar',
            'KZT' => 'KZT - Kazakhstani Tenge',
            'LAK' => 'LAK - Laotian Kip',
            'LBP' => 'LBP - Lebanese Pound',
            'LKR' => 'LKR - Sri Lankan Rupee',
            'LRD' => 'LRD - Liberian Dollar',
            'LSL' => 'LSL - Lesotho Loti',
            'LYD' => 'LYD - Libyan Dinar',
            'MAD' => 'MAD - Moroccan Dirham',
            'MDL' => 'MDL - Moldovan Leu',
            'MGA' => 'MGA - Malagasy Ariary',
            'MKD' => 'MKD - Macedonian Denar',
            'MMK' => 'MMK - Myanmar Kyat',
            'MNT' => 'MNT - Mongolian Tugrik',
            'MOP' => 'MOP - Macanese Pataca',
            'MRU' => 'MRU - Mauritanian Ouguiya',
            'MUR' => 'MUR - Mauritian Rupee',
            'MVR' => 'MVR - Maldivian Rufiyaa',
            'MWK' => 'MWK - Malawian Kwacha',
            'MXN' => 'MXN - Mexican Peso',
            'MYR' => 'MYR - Malaysian Ringgit',
            'MZN' => 'MZN - Mozambican Metical',
            'NAD' => 'NAD - Namibian Dollar',
            'NGN' => 'NGN - Nigerian Naira',
            'NIO' => 'NIO - Nicaraguan Córdoba',
            'NOK' => 'NOK - Norwegian Krone',
            'NPR' => 'NPR - Nepalese Rupee',
            'NZD' => 'NZD - New Zealand Dollar',
            'OMR' => 'OMR - Omani Rial',
            'PAB' => 'PAB - Panamanian Balboa',
            'PEN' => 'PEN - Peruvian Sol',
            'PGK' => 'PGK - Papua New Guinean Kina',
            'PHP' => 'PHP - Philippine Peso',
            'PKR' => 'PKR - Pakistani Rupee',
            'PLN' => 'PLN - Polish Zloty',
            'PYG' => 'PYG - Paraguayan Guarani',
            'QAR' => 'QAR - Qatari Rial',
            'RON' => 'RON - Romanian Leu',
            'RSD' => 'RSD - Serbian Dinar',
            'RUB' => 'RUB - Russian Ruble',
            'RWF' => 'RWF - Rwandan Franc',
            'SAR' => 'SAR - Saudi Riyal',
            'SBD' => 'SBD - Solomon Islands Dollar',
            'SCR' => 'SCR - Seychellois Rupee',
            'SDG' => 'SDG - Sudanese Pound',
            'SEK' => 'SEK - Swedish Krona',
            'SGD' => 'SGD - Singapore Dollar',
            'SHP' => 'SHP - Saint Helena Pound',
            'SLL' => 'SLL - Sierra Leonean Leone',
            'SOS' => 'SOS - Somali Shilling',
            'SRD' => 'SRD - Surinamese Dollar',
            'SSP' => 'SSP - South Sudanese Pound',
            'STN' => 'STN - São Tomé and Príncipe Dobra',
            'SYP' => 'SYP - Syrian Pound',
            'SZL' => 'SZL - Swazi Lilangeni',
            'THB' => 'THB - Thai Baht',
            'TJS' => 'TJS - Tajikistani Somoni',
            'TMT' => 'TMT - Turkmenistani Manat',
            'TND' => 'TND - Tunisian Dinar',
            'TOP' => 'TOP - Tongan Paʻanga',
            'TRY' => 'TRY - Turkish Lira',
            'TTD' => 'TTD - Trinidad and Tobago Dollar',
            'TWD' => 'TWD - New Taiwan Dollar',
            'TZS' => 'TZS - Tanzanian Shilling',
            'UAH' => 'UAH - Ukrainian Hryvnia',
            'UGX' => 'UGX - Ugandan Shilling',
            'USD' => 'USD - US Dollar',
            'UYU' => 'UYU - Uruguayan Peso',
            'UZS' => 'UZS - Uzbekistani Som',
            'VES' => 'VES - Venezuelan Bolívar',
            'VND' => 'VND - Vietnamese Dong',
            'VUV' => 'VUV - Vanuatu Vatu',
            'WST' => 'WST - Samoan Tala',
            'XAF' => 'XAF - Central African CFA Franc',
            'XCD' => 'XCD - East Caribbean Dollar',
            'XOF' => 'XOF - West African CFA Franc',
            'XPF' => 'XPF - CFP Franc',
            'YER' => 'YER - Yemeni Rial',
            'ZAR' => 'ZAR - South African Rand',
            'ZMW' => 'ZMW - Zambian Kwacha',
            'ZWL' => 'ZWL - Zimbabwean Dollar',
        ];
    }
}
