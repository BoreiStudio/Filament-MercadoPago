<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources;

use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models\WebhookEvent;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\Pages\ManageWebhookEvents;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static ?string $slug = 'webhook-events';

    protected static ?string $recordTitleAttribute = 'mp_resource_id';

    protected static ?int $navigationSort = 6;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-path-rounded-square';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Mercado Pago';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWebhookEvents::route('/'),
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
