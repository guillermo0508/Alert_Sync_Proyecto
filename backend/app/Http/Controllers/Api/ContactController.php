<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactVerification;
use App\Models\Contact;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    private const VERIFICATION_TTL_DAYS = 7;
    private const RESEND_COOLDOWN_MINUTES = 2;

    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::where('user_id', (string) $request->user()->_id)
            ->orderBy('priority')
            ->get();

        return response()->json(['contacts' => $contacts]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentCount = Contact::where('user_id', (string) $user->_id)->count();

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
            'user_id' => (string) $request->user()->_id,
            'notify_sms' => $validated['notify_sms'] ?? true,
            'notify_call' => $validated['notify_call'] ?? false,
            'notify_email' => $validated['notify_email'] ?? false,
            'priority' => $validated['priority'] ?? 1,
            'verified' => false,
        ]);

        if ($contact->email) {
            $this->issueVerification($contact, $user->name);
        }

        return response()->json([
            'message' => 'Contacto agregado correctamente.',
            'contact' => $contact,
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

        if ($emailChanged) {
            if ($contact->email) {
                $this->issueVerification($contact->fresh(), $request->user()->name);
            } else {
                $contact->verified = false;
                $contact->verification_token = null;
                $contact->verification_token_expires_at = null;
                $contact->save();
            }
        }

        return response()->json([
            'message' => 'Contacto actualizado correctamente.',
            'contact' => $contact->fresh(),
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

        $cooldownEnd = now()->addDays(self::VERIFICATION_TTL_DAYS)->subMinutes(self::RESEND_COOLDOWN_MINUTES);
        if ($contact->verification_token_expires_at && $contact->verification_token_expires_at->gt($cooldownEnd)) {
            return response()->json(['message' => 'Ya se envió una confirmación hace poco. Espera unos minutos antes de reenviar.'], 429);
        }

        $this->issueVerification($contact, $request->user()->name);

        return response()->json(['message' => "Se reenvió el correo de confirmación a {$contact->email}."]);
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
            return response()->json(['message' => 'Este contacto ya había sido confirmado.', 'contact_name' => $contact->name]);
        }

        if (! $contact->verification_token || ! $contact->verification_token_expires_at || $contact->verification_token_expires_at->isPast()) {
            return response()->json(['message' => 'El enlace de confirmación expiró. Pide que te reenvíen la invitación.'], 422);
        }

        if (! Hash::check($validated['token'], $contact->verification_token)) {
            return response()->json(['message' => 'Enlace de confirmación inválido.'], 422);
        }

        $contact->verified = true;
        $contact->verification_token = null;
        $contact->verification_token_expires_at = null;
        $contact->save();

        return response()->json(['message' => 'Contacto confirmado correctamente.', 'contact_name' => $contact->name]);
    }

    private function issueVerification(Contact $contact, string $ownerName): void
    {
        $token = Str::random(40);
        $contact->verification_token = Hash::make($token);
        $contact->verification_token_expires_at = now()->addDays(self::VERIFICATION_TTL_DAYS);
        $contact->verified = false;
        $contact->save();

        $verifyUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/')
            . "/verify-contact/{$contact->id}?token={$token}";

        try {
            Mail::to($contact->email)->send(new ContactVerification($contact->name, $ownerName, $verifyUrl));
        } catch (\Exception $e) {
            Log::error('Error sending contact verification email: ' . $e->getMessage());
        }
    }

    private function findUserContact(Request $request, string $id): Contact
    {
        return Contact::where('_id', $id)
            ->where('user_id', (string) $request->user()->_id)
            ->firstOrFail();
    }
}
