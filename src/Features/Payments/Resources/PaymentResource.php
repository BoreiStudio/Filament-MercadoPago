<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Resources;

use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\Pages\ManagePayments;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $slug = 'payments';

    protected static ?string $recordTitleAttribute = 'mp_payment_id';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
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
