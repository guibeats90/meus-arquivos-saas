<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '15s';

    protected static bool $isLazy = true;

    /**
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // Calcular os ganhos e perdas dos usuários nos últimos 7 dias com base na tabela "orders"
        $totalWonLast7Days = Order::where('type', 'win')->where('created_at', '>=', $sevenDaysAgo)->sum('amount');
        $totalLoseLast7Days = Order::where('type', 'loss')->where('created_at', '>=', $sevenDaysAgo)->sum('amount');

        return [
            Stat::make('Total Usuários', User::where('role_id', 3)->count())
                ->description('Novos usuários')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Total Ganhos', \Helper::amountFormatDecimal($totalWonLast7Days))
                ->description('Ganhos dos usuários')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Total Perdas', \Helper::amountFormatDecimal($totalLoseLast7Days))
                ->description('Perdas dos usuários')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
        ];
    }
}
