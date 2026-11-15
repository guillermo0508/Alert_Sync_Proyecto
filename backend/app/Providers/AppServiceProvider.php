<?php

namespace App\Providers;

use App\Mail\Transport\HttpRelayTransport;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // MAIL_MAILER=relay: los correos se mandan al servicio mail-relay (Vercel),
        // que los envía por SMTP de Gmail. Render gratuito bloquea los puertos SMTP.
        Mail::extend('relay', fn (array $config) => new HttpRelayTransport(
            url: (string) ($config['endpoint'] ?? ''),
            secret: (string) ($config['secret'] ?? ''),
        ));
    }
}
