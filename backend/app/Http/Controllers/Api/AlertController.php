<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SosAlert;
use App\Mail\SosCancelled;
use App\Models\Alert;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertController extends Controller
{
    /** Zona horaria con la que se muestran las fechas en los correos. */
    private const MAIL_TIMEZONE = 'America/Mexico_City';

    public function index(Request $request): JsonResponse
    {
        $alerts = Alert::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['alerts' => $alerts]);
    }

    public function trigger(Request $request): JsonResponse
    {
        if (! $request->filled('source')) {
            $request->merge(['source' => 'watch']);
        }

        $validated = $request->validate([
            'source' => ['required', 'in:watch,alexa,dashboard,dashboard-premium,mobile,web,simulator'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $contacts = Contact::where('user_id', $user->id)->orderBy('priority')->get();

        $notified = $contacts->map(fn (Contact $contact) => [
            'id' => (string) $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'channels' => array_values(array_filter([
                $contact->notify_sms ? 'sms' : null,
                $contact->notify_email ? 'email' : null,
            ])),
        ])->all();

        $alert = Alert::create([
            'user_id' => $user->id,
            'source' => $validated['source'],
            'status' => 'sent',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'message' => $validated['message'] ?? 'Alerta SOS activada — se requiere ayuda inmediata.',
            'contacts_notified' => $notified,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        $emailCount = count(array_filter($notified, fn ($c) => $this->wantsEmail($c)));

        // Los correos se envían después de responder, para que la app no espere al servidor SMTP.
        $ownerName = $user->name ?: 'Un usuario de ALERT SYNC';
        $ownerPhone = $user->phone ?: null;
        dispatch(function () use ($alert, $notified, $ownerName, $ownerPhone) {
            $this->sendSosEmails($alert, $notified, $ownerName, $ownerPhone);
        })->afterResponse();

        return response()->json([
            'message' => 'Alerta SOS enviada correctamente.',
            'alert' => $alert,
            'contacts_count' => count($notified),
            'emails_count' => $emailCount,
        ], 201);
    }

    /**
     * Cancela una alerta enviada y avisa por correo a los contactos que la recibieron.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $alert = Alert::where('user_id', $user->id)->whereKey($id)->first();

        if (! $alert) {
            return response()->json(['message' => 'Alerta no encontrada.'], 404);
        }

        if ($alert->status === 'cancelled') {
            return response()->json(['message' => 'La alerta ya estaba cancelada.', 'alert' => $alert]);
        }

        $metadata = $alert->metadata ?? [];
        $metadata['cancelled_at'] = now()->toIso8601String();
        $alert->status = 'cancelled';
        $alert->metadata = $metadata;
        $alert->save();

        $ownerName = $user->name ?: 'Un usuario de ALERT SYNC';
        $notified = $alert->contacts_notified ?? [];
        dispatch(function () use ($alert, $notified, $ownerName) {
            $this->sendCancellationEmails($alert, $notified, $ownerName);
        })->afterResponse();

        return response()->json([
            'message' => 'Alerta cancelada. Se avisó a tus contactos.',
            'alert' => $alert,
        ]);
    }

    // ---------------------------------------------------------------------
    // Envío de correos
    // ---------------------------------------------------------------------

    private function wantsEmail(array $contact): bool
    {
        return ! empty($contact['email']) && in_array('email', $contact['channels'] ?? [], true);
    }

    private function formatDate(?Carbon $date): string
    {
        return ($date ?? now())->copy()->timezone(self::MAIL_TIMEZONE)->format('d/m/Y h:i A');
    }

    private function sendSosEmails(Alert $alert, array $notified, string $ownerName, ?string $ownerPhone): void
    {
        $mapsUrl = ($alert->latitude !== null && $alert->longitude !== null)
            ? "https://www.google.com/maps/search/?api=1&query={$alert->latitude},{$alert->longitude}"
            : null;
        $sentAt = $this->formatDate($alert->created_at);

        foreach ($notified as $contact) {
            if (! $this->wantsEmail($contact)) {
                continue;
            }
            try {
                Mail::to($contact['email'])->send(new SosAlert(
                    contactName: $contact['name'] ?? 'contacto',
                    ownerName: $ownerName,
                    alertMessage: $alert->message,
                    sentAt: $sentAt,
                    mapsUrl: $mapsUrl,
                    ownerPhone: $ownerPhone,
                ));
            } catch (\Throwable $e) {
                Log::error("Error enviando correo SOS (alerta {$alert->id}) a {$contact['email']}: " . $e->getMessage());
            }
            // SMS: pendiente de configurar un proveedor (p. ej. Twilio). Los contactos con canal 'sms'
            // quedan registrados en contacts_notified para cuando se integre.
        }
    }

    private function sendCancellationEmails(Alert $alert, array $notified, string $ownerName): void
    {
        $sentAt = $this->formatDate($alert->created_at);
        $cancelledAt = $this->formatDate(now());

        foreach ($notified as $contact) {
            if (! $this->wantsEmail($contact)) {
                continue;
            }
            try {
                Mail::to($contact['email'])->send(new SosCancelled(
                    contactName: $contact['name'] ?? 'contacto',
                    ownerName: $ownerName,
                    sentAt: $sentAt,
                    cancelledAt: $cancelledAt,
                ));
            } catch (\Throwable $e) {
                Log::error("Error enviando correo de cancelación (alerta {$alert->id}) a {$contact['email']}: " . $e->getMessage());
            }
        }
    }
}
