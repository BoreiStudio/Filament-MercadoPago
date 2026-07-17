<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mercadopago-app.client_id', '');
        $this->migrator->addEncrypted('mercadopago-app.client_secret', '');
        $this->migrator->add('mercadopago-app.public_key', '');
        $this->migrator->addEncrypted('mercadopago-app.access_token', '');
        $this->migrator->add('mercadopago-app.redirect_uri', '');
        $this->migrator->addEncrypted('mercadopago-app.webhook_secret', '');
        $this->migrator->add('mercadopago-app.country', 'MLA');
        $this->migrator->add('mercadopago-app.sandbox_mode', false);
    }

    public function down(): void
    {
        $this->migrator->delete('mercadopago-app.client_id');
        $this->migrator->delete('mercadopago-app.client_secret');
        $this->migrator->delete('mercadopago-app.public_key');
        $this->migrator->delete('mercadopago-app.access_token');
        $this->migrator->delete('mercadopago-app.redirect_uri');
        $this->migrator->delete('mercadopago-app.webhook_secret');
        $this->migrator->delete('mercadopago-app.country');
        $this->migrator->delete('mercadopago-app.sandbox_mode');
    }
};
