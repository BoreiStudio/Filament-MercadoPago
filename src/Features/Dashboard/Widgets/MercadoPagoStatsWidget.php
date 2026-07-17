<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Dashboard\Widgets;

use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MercadoPagoStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('viewAny', MercadoPagoAccount::class);
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $week = now()->startOfWeek();
        $month = now()->startOfMonth();

        $todayTotal = Payment::where('status', 'approved')
            ->where('created_at', '>=', $today)
            ->sum('transaction_amount');

        $weekTotal = Payment::where('status', 'approved')
            ->where('created_at', '>=', $week)
            ->sum('transaction_amount');

        $monthTotal = Payment::where('status', 'approved')
            ->where('created_at', '>=', $month)
            ->sum('transaction_amount');

        $bySource = Payment::where('status', 'approved')
            ->select('source', DB::raw('SUM(transaction_amount) as total'))
            ->groupBy('source')
            ->pluck('total', 'source');

        $sources = [];
        foreach (['checkout_pro', 'point', 'qr'] as $src) {
            $amount = (float) ($bySource[$src] ?? 0);
            if ($amount > 0) {
                $sources[] = __('filament-mercadopago::messages.dashboard.source_'.$src).': $'.number_format($amount, 2, ',', '.');
            }
        }

        $description = ! empty($sources)
            ? implode(' · ', $sources)
            : __('filament-mercadopago::messages.dashboard.month_empty');

        return [
            Stat::make(__('filament-mercadopago::messages.dashboard.today'), '$'.number_format($todayTotal, 2, ',', '.'))
                ->description(__('filament-mercadopago::messages.dashboard.today_description'))
                ->descriptionIcon('heroicon-m-calendar'),

            Stat::make(__('filament-mercadopago::messages.dashboard.week'), '$'.number_format($weekTotal, 2, ',', '.'))
                ->description(__('filament-mercadopago::messages.dashboard.week_description'))
                ->descriptionIcon('heroicon-m-presentation-chart-bar'),

            Stat::make(__('filament-mercadopago::messages.dashboard.month'), '$'.number_format($monthTotal, 2, ',', '.'))
                ->description($description)
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
        ];
    }
}
