<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactVerification;
use App\Models\Contact;
use App\Models\Plan;
use App\Support\FrontendUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    /** Mientras la invitación sigue pendiente, no se puede reenviar antes de este tiempo. */
    private const RESEND_COOLDOWN_MINUTES = 1;

    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::where('user_id', $request->user()->id)
            ->orderBy('priority')
            ->get();

        return response()->json(['contacts' => $contacts]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentCount = Contact::where('user_id', $user->id)->count();

        $planId = match ($user->plan) {
            'Premium' => 'premium',
            'Básico' => 'basico',
            default => null,
        };

        $maxContacts = $planId ? Plan::getOrDefault($planId)->max_contacts : 0;

        if ($currentCount >= $maxContacts) {
            return response()->json([
                'message' => "Límite de contactos alcanzado para tu plan ({$user->plan})."
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:60'],
            'notify_sms' => ['boolean'],
            'notify_call' => ['boolean'],
            'notify_email' => ['boolean'],
            'priority' => ['integer', 'min:1', 'max:99'],
        ]);

        $contact = Contact::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'notify_sms' => $validated['notify_sms'] ?? true,
            'notify_call' => $validated['notify_call'] ?? false,
            'notify_email' => $validated['notify_email'] ?? false,
            'priority' => $validated['priority'] ?? 1,
            'verified' => false,
        ]);

        $mailSent = true;
        if ($contact->email) {
            $mailSent = $this->issueVerification($contact, $user->name, $request);
        }

        return response()->json([
            'message' => $mailSent
                ? 'Contacto agregado correctamente.'
                : 'Contacto agregado, pero no se pudo enviar el correo de confirmación. Usa "Reenviar correo" en unos minutos.',
            'contact' => $contact,
            'mail_sent' => $mailSent,
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $contact = $this->findUserContact($request, $id);

        return response()->json(['contact' => $contact]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $contact = $this->findUserContact($request, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:60'],
            'notify_sms' => ['boolean'],
            'notify_call' => ['boolean'],
            'notify_email' => ['boolean'],
            'priority' => ['integer', 'min:1', 'max:99'],
        ]);

        $emailChanged = array_key_exists('email', $validated) && $validated['email'] !== $contact->email;

        $contact->update($validated);

        $mailSent = true;
        if ($emailChanged) {
            if ($contact->email) {
                $mailSent = $this->issueVerification($contact->fresh(), $request->user()->name, $request);
            } else {
                $contact->verified = false;
                $contact->declined_at = null;
                $contact->verification_token = null;
                $contact->verification_token_expires_at = null;
                $contact->save();
            }
        }

        return response()->json([
            'message' => $mailSent
                ? 'Contacto actualizado correctamente.'
                : 'Contacto actualizado, pero no se pudo enviar el correo de confirmación. Usa "Reenviar correo" en unos minutos.',
            'contact' => $contact->fresh(),
            'mail_sent' => $mailSent,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $contact = $this->findUserContact($request, $id);
        $contact->delete();

        return response()->json(['message' => 'Contacto eliminado correctamente.']);
    }

    public function resendVerification(Request $request, string $id): JsonResponse
    {
        $contact = $this->findUserContact($request, $id);

        if (! $contact->email) {
            return response()->json(['message' => 'Este contacto no tiene correo registrado.'], 422);
        }

        if ($contact->verified) {
            return response()->json(['message' => 'Este contacto ya está confirmado.'], 422);
        }

        // Solo mientras sigue pendiente: evita enviar varios correos seguidos.
        if ($contact->status === Contact::STATUS_PENDING) {
            $sentAt = $contact->verification_token_expires_at->copy()->subMinutes(Contact::RESPONSE_MINUTES);
            if ($sentAt->gt(now()->subMinutes(self::RESEND_COOLDOWN_MINUTES))) {
                return response()->json(['message' => 'Ya se envió una confirmación hace poco. Espera un minuto antes de reenviar.'], 429);
            }
        }

        if (! $this->issueVerification($contact, $request->user()->name, $request)) {
            return response()->json([
                'message' => 'No se pudo enviar el correo de confirmación. Intenta de nuevo en unos minutos.',
                'mail_sent' => false,
            ], 502);
        }

        return response()->json([
            'message' => "Se reenvió el correo de confirmación a {$contact->email}. Si no lo ves, revisa Spam o Promociones.",
            'mail_sent' => true,
        ]);
    }

    public function verify(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $contact = Contact::find($id);

        if (! $contact) {
            return response()->json(['message' => 'Contacto no encontrado.'], 404);
        }

        if ($contact->verified) {
            return response()->json(['message' => 'Este contacto ya había sido confirmado.', 'contact_name' => $contact->name, 'status' => $contact->status]);
        }

        if ($error = $this->invitationError($contact, $validated['token'])) {
            return $error;
        }

        $contact->verified = true;
        $contact->declined_at = null;
        $contact->verification_token = null;
        $contact->verification_token_expires_at = null;
        $contact->save();

        return response()->json(['message' => 'Contacto confirmado correctamente.', 'contact_name' => $contact->name, 'status' => $contact->status]);
    }

    /** El contacto rechaza ser contacto de emergencia (botón "No acepto" del correo). */
    public function decline(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);

        $contact = Contact::find($id);
        if (! $contact) {
            return response()->json(['message' => 'Contacto no encontrado.'], 404);
        }
        if ($contact->verified) {
            return response()->json(['message' => 'Ya habías aceptado ser contacto de emergencia.', 'status' => $contact->status], 422);
        }
        if ($error = $this->invitationError($contact, $validated['token'])) {
            return $error;
        }

        $contact->declined_at = now();
        $contact->verification_token = null;
        $contact->verification_token_expires_at = null;
        $contact->save();

        return response()->json(['message' => 'Listo, no recibirás alertas de esta persona.', 'contact_name' => $contact->name, 'status' => $contact->status]);
    }

    /** Error si la invitación ya no se puede responder (rechazada, vencida o enlace inválido). */
    private function invitationError(Contact $contact, string $token): ?JsonResponse
    {
        if ($contact->declined_at) {
            return response()->json(['message' => 'Ya rechazaste esta invitación.', 'status' => Contact::STATUS_REJECTED], 422);
        }
        if (! $contact->verification_token || ! $contact->verification_token_expires_at || $contact->verification_token_expires_at->isPast()) {
            return response()->json([
                'message' => 'El tiempo para responder ('.Contact::RESPONSE_MINUTES.' minutos) terminó y la invitación quedó rechazada. Pide que te la reenvíen.',
                'status' => Contact::STATUS_REJECTED,
            ], 422);
        }
        if (! Hash::check($token, $contact->verification_token)) {
            return response()->json(['message' => 'Enlace de confirmación inválido.'], 422);
        }

        return null;
    }

    /** Genera una invitación nueva y envía el correo. Devuelve false si el correo no se pudo enviar. */
    private function issueVerification(Contact $contact, string $ownerName, Request $request): bool
    {
        $token = Str::random(40);
        $contact->verification_token = Hash::make($token);
        $contact->verification_token_expires_at = now()->addMinutes(Contact::RESPONSE_MINUTES);
        $contact->verified = false;
        $contact->declined_at = null;
        $contact->save();

        // El enlace apunta a la página web desde la que se guardó el contacto (o a FRONTEND_URL).
        $verifyUrl = FrontendUrl::to("verify-contact/{$contact->id}?token={$token}", $request);
        $declineUrl = $verifyUrl.'&action=decline';

        try {
            Mail::to($contact->email)->send(new ContactVerification($contact->name, $ownerName, $verifyUrl, $declineUrl, Contact::RESPONSE_MINUTES));

            return true;
        } catch (\Throwable $e) {
            Log::error("Error enviando correo de confirmación al contacto {$contact->id} ({$contact->email}): " . $e->getMessage());

            return false;
        }
    }

    private function findUserContact(Request $request, string $id): Contact
    {
        return Contact::whereKey($id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
