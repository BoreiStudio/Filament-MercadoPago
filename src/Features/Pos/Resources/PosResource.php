<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Resources;

use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\Pages\ManagePosTerminals;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class PosResource extends Resource
{
    protected static ?string $model = PosTerminal::class;

    protected static ?string $slug = 'pos';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-computer-desktop';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Mercado Pago';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePosTerminals::route('/'),
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
