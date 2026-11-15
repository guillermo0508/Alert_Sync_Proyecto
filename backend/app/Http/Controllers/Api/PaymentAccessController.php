<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PaymentAccessCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Verificación por correo antes de ver/editar el método de pago en la app.
 * 1) POST /auth/payment-access/send   → envía un código de 6 dígitos al correo del usuario.
 * 2) POST /auth/payment-access/verify → valida el código (un solo uso).
 */
class PaymentAccessController extends Controller
{
    private const CODE_TTL_MINUTES = 10;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        // Evita reenvíos seguidos: si el código se generó hace menos de 60 s, no se manda otro.
        if ($user->payment_access_code_expires_at
            && $user->payment_access_code_expires_at->gt(now()->addMinutes(self::CODE_TTL_MINUTES)->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            return response()->json([
                'message' => 'Ya te enviamos un código. Espera un minuto para pedir otro.',
                'email' => $this->maskEmail($user->email),
                'expires_in_minutes' => self::CODE_TTL_MINUTES,
            ], 429);
        }

        $code = (string) random_int(100000, 999999);
        $user->payment_access_code = Hash::make($code);
        $user->payment_access_code_expires_at = now()->addMinutes(self::CODE_TTL_MINUTES);
        $user->save();

        $userName = $user->name;
        $email = $user->email;
        dispatch(function () use ($userName, $email, $code) {
            try {
                Mail::to($email)->send(new PaymentAccessCode($userName, $code, self::CODE_TTL_MINUTES));
            } catch (\Throwable $e) {
                Log::error('Error enviando código de acceso a método de pago: ' . $e->getMessage());
            }
        })->afterResponse();

        return response()->json([
            'message' => 'Te enviamos un código de verificación a tu correo.',
            'email' => $this->maskEmail($email),
            'expires_in_minutes' => self::CODE_TTL_MINUTES,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        if (! $user->payment_access_code || ! $user->payment_access_code_expires_at
            || $user->payment_access_code_expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => ['El código expiró. Solicita uno nuevo.']]);
        }
        if (! Hash::check($validated['code'], $user->payment_access_code)) {
            throw ValidationException::withMessages(['code' => ['Código incorrecto.']]);
        }

        $user->payment_access_code = null;
        $user->payment_access_code_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Verificación correcta.', 'verified' => true]);
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, 2);
        return $visible . str_repeat('•', max(1, mb_strlen($name) - 2)) . '@' . $domain;
    }
}
