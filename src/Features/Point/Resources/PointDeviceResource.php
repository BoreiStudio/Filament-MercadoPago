<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Resources;

use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Point\Resources\Pages\ManagePointDevices;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class PointDeviceResource extends Resource
{
    protected static ?string $model = PointDevice::class;

    protected static ?string $slug = 'point-devices';

    protected static ?string $recordTitleAttribute = 'device_id';

    protected static ?int $navigationSort = 5;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-device-phone-mobile';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Mercado Pago';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePointDevices::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
