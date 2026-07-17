<?php

namespace BoreiStudio\FilamentMercadoPago\Tests;

use BoreiStudio\FilamentMercadoPago\MercadoPagoServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'BoreiStudio\\FilamentMercadoPago\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->createSettingsTable();
        $this->runPluginMigrations();
    }

    protected function getPackageProviders($app)
    {
        return [
            FilamentServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            TablesServiceProvider::class,
            NotificationsServiceProvider::class,
            InfolistsServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            MercadoPagoServiceProvider::class,
            LaravelSettingsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:V4s8xOXyGq3fRnLp9mWcJhB7dKtZvYaE2wNo6rUgC1M=');
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $app['config']->set('mercadopago.mode', 'single_tenant');
    }

    private function createSettingsTable(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['group', 'name']);
        });
    }

    private function runPluginMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate', ['--force' => true, '--realpath' => true])->run();
    }
}
