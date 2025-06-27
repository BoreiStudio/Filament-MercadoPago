<?php

namespace BoreiStudio\FilamentMercadoPago\Resources\PlanResource\Pages;

use BoreiStudio\FilamentMercadoPago\Resources\PlanResource;
use Filament\Resources\Pages\CreateRecord;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoPlanService;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoPlan;
use Exception;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function handleRecordCreation(array $data): MercadoPagoPlan
    {
        try {
            // Crear en MP y en la DB
            $result = app(MercadoPagoPlanService::class)->createPlan($data);

            // Buscar el registro que acabamos de crear para devolverlo
            return MercadoPagoPlan::where('external_id', $result['id'])->firstOrFail();

        } catch (Exception $e) {
            $this->notify('danger', 'Error al crear el plan en Mercado Pago: ' . $e->getMessage());
            throw $e;
        }
    }
}
