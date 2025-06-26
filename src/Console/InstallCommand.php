<?php

namespace BoreiStudio\FilamentMercadoPago\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'filament-mercado-pago:install';

    protected $description = 'Publica config y migraciones del plugin FilamentMercadoPago';

    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag' => 'filament-mercado-pago-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'filament-mercado-pago-migrations',
        ]);

        $this->info('Instalación completa.');
    }
}
