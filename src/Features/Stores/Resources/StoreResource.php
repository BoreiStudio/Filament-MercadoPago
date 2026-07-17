<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Resources;

use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\Pages\ManageStores;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $slug = 'stores';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-storefront';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Mercado Pago';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStores::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }
}
