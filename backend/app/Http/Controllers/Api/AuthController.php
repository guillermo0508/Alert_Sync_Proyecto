<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthUser;
use App\Http\Controllers\Controller;
use App\Mail\ActivationCode;
use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use FormatsAuthUser;

    private const CODE_TTL_MINUTES = 15;
    private const CODE_RESEND_COOLDOWN_SECONDS = 30;

    /**
     * Resolves a login identifier that can be either a unique username (used by
     * admin accounts that share a contact email) or an email address. Email
     * lookups only resolve when exactly one account uses that address, since
     * multiple admins may intentionally share the same contact email.
     */
    private function resolveIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);

        $byUsername = User::where('username', $identifier)->first();
        if ($byUsername) {
            return $byUsername;
        }

        $matches = User::where('email', strtolower($identifier))->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^\pL+(\s\pL+)+$/u'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'name.regex' => 'Ingresa tu nombre completo (nombre y apellido), sin números.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'Ingresa un número de teléfono válido con código de país.',
        ]);

        if (User::where('email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Este correo ya está registrado.'],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'plan' => 'Demo',
            'plan_expires_at' => null,
            'next_plan' => null,
            'devices' => ['watch' => false, 'alexa' => false],
            'status' => 'active',
        ]);

        $token = $user->createToken('alertsync-web')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada exitosamente.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->resolveIdentifier($validated['email']);

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Correo o contraseña incorrectos.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta aún no ha sido activada. Ingresa tu correo para recibir un código de verificación.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('alertsync-web')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->save();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Contraseña actualizada. Inicia sesión con tu nueva contraseña.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if plan expired
        if ($user->plan !== 'Demo' && $user->plan_expires_at && $user->plan_expires_at->isPast()) {
            if ($user->next_plan) {
                $user->plan = $user->next_plan;
                $user->next_plan = null;
                $user->plan_expires_at = now()->addDays(30);
                $user->save();
            } else {
                $user->plan = 'Demo';
                $user->plan_expires_at = null;
                $user->save();
            }
        }

        return response()->json(['user' => $this->formatUser($user)]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
        ]);

        $identifier = trim($validated['email']);

        // Try a username match first — this also covers the admin sub-step below,
        // where the identifier submitted is actually a username, not an email.
        $user = User::where('username', $identifier)->first();

        if (! $user) {
            $matches = User::where('email', strtolower($identifier))->get();

            if ($matches->isEmpty()) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'No existe una cuenta con ese correo.',
                ], 404);
            }

            // Admins may share one contact email between several accounts, so email alone
            // can't identify which admin is signing in — ask for their username instead.
            if ($matches->count() > 1 || $matches->first()->role === 'admin') {
                return response()->json([
                    'status' => 'needs_username',
                    'message' => 'Este correo pertenece a un administrador. Ingresa tu usuario para continuar.',
                ]);
            }

            $user = $matches->first();
        }

        return $this->respondAccountStatus($user);
    }

    private function respondAccountStatus(User $user): JsonResponse
    {
        if ($user->isActive()) {
            return response()->json(['status' => 'active']);
        }

        $freshWindowEnd = now()->addSeconds((self::CODE_TTL_MINUTES * 60) - self::CODE_RESEND_COOLDOWN_SECONDS);
        $codeStillFresh = $user->activation_code_expires_at
            && $user->activation_code_expires_at->gt($freshWindowEnd);

        if (! $codeStillFresh) {
            $this->issueActivationCode($user);
        }

        return response()->json([
            'status' => 'pending',
            'message' => 'Tu cuenta aún no está activada. Te enviamos un código de verificación a tu correo, vence en ' . self::CODE_TTL_MINUTES . ' minutos.',
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $user = $this->resolveIdentifier($validated['email']);

        if (! $user || $user->isActive()) {
            throw ValidationException::withMessages([
                'code' => ['Solicitud inválida.'],
            ]);
        }

        if (! $user->activation_code || ! $user->activation_code_expires_at || $user->activation_code_expires_at->isPast()) {
            $this->issueActivationCode($user);

            throw ValidationException::withMessages([
                'code' => ['Tu código expiró. Te enviamos uno nuevo a tu correo.'],
            ]);
        }

        if (! Hash::check($validated['code'], $user->activation_code)) {
            throw ValidationException::withMessages([
                'code' => ['El código es incorrecto.'],
            ]);
        }

        $activationToken = Str::random(40);
        $user->activation_token = Hash::make($activationToken);
        $user->activation_token_expires_at = now()->addMinutes(10);
        $user->activation_code = null;
        $user->activation_code_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Código verificado. Ahora crea tu contraseña.',
            'activation_token' => $activationToken,
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'activation_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $this->resolveIdentifier($validated['email']);

        if (! $user || $user->isActive()) {
            throw ValidationException::withMessages([
                'activation_token' => ['Solicitud inválida.'],
            ]);
        }

        if (! $user->activation_token || ! $user->activation_token_expires_at || $user->activation_token_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'activation_token' => ['El proceso expiró. Vuelve a ingresar tu correo para solicitar un nuevo código.'],
            ]);
        }

        if (! Hash::check($validated['activation_token'], $user->activation_token)) {
            throw ValidationException::withMessages([
                'activation_token' => ['Solicitud inválida.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->status = 'active';
        $user->activation_token = null;
        $user->activation_token_expires_at = null;
        $user->save();

        $user->tokens()->delete();
        $token = $user->createToken('alertsync-web')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta activada correctamente.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    private function issueActivationCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->activation_code = Hash::make($code);
        $user->activation_code_expires_at = now()->addMinutes(self::CODE_TTL_MINUTES);
        $user->save();

        try {
            Mail::to($user->email)->send(new ActivationCode($user->name, $code, self::CODE_TTL_MINUTES));
        } catch (\Exception $e) {
            Log::error('Error sending activation code email: ' . $e->getMessage());
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
        ]);

        $user = $this->resolveIdentifier($validated['email']);

        if (! $user) {
            return response()->json([
                'message' => 'No existe una cuenta con ese correo o usuario.',
            ], 404);
        }

        if (! $user->isActive()) {
            return response()->json([
                'message' => 'Tu cuenta aún no ha sido activada. Ingresa tu correo en el inicio de sesión para activarla primero.',
            ], 422);
        }

        $freshWindowEnd = now()->addSeconds((self::CODE_TTL_MINUTES * 60) - self::CODE_RESEND_COOLDOWN_SECONDS);
        $codeStillFresh = $user->password_reset_code_expires_at
            && $user->password_reset_code_expires_at->gt($freshWindowEnd);

        if (! $codeStillFresh) {
            $this->issuePasswordResetCode($user);
        }

        return response()->json([
            'message' => 'Te enviamos un código para restablecer tu contraseña, vence en ' . self::CODE_TTL_MINUTES . ' minutos.',
        ]);
    }

    public function verifyResetCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $user = $this->resolveIdentifier($validated['email']);

        if (! $user || ! $user->isActive()) {
            throw ValidationException::withMessages([
                'code' => ['Solicitud inválida.'],
            ]);
        }

        if (! $user->password_reset_code || ! $user->password_reset_code_expires_at || $user->password_reset_code_expires_at->isPast()) {
            $this->issuePasswordResetCode($user);

            throw ValidationException::withMessages([
                'code' => ['Tu código expiró. Te enviamos uno nuevo a tu correo.'],
            ]);
        }

        if (! Hash::check($validated['code'], $user->password_reset_code)) {
            throw ValidationException::withMessages([
                'code' => ['El código es incorrecto.'],
            ]);
        }

        $resetToken = Str::random(40);
        $user->password_reset_token = Hash::make($resetToken);
        $user->password_reset_token_expires_at = now()->addMinutes(10);
        $user->password_reset_code = null;
        $user->password_reset_code_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Código verificado. Ahora crea tu nueva contraseña.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $this->resolveIdentifier($validated['email']);

        if (! $user || ! $user->isActive()) {
            throw ValidationException::withMessages([
                'reset_token' => ['Solicitud inválida.'],
            ]);
        }

        if (! $user->password_reset_token || ! $user->password_reset_token_expires_at || $user->password_reset_token_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'reset_token' => ['El proceso expiró. Vuelve a solicitar un código.'],
            ]);
        }

        if (! Hash::check($validated['reset_token'], $user->password_reset_token)) {
            throw ValidationException::withMessages([
                'reset_token' => ['Solicitud inválida.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->password_reset_token = null;
        $user->password_reset_token_expires_at = null;
        $user->save();

        $user->tokens()->delete();
        $token = $user->createToken('alertsync-web')->plainTextToken;

        return response()->json([
            'message' => 'Contraseña restablecida correctamente.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    private function issuePasswordResetCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->password_reset_code = Hash::make($code);
        $user->password_reset_code_expires_at = now()->addMinutes(self::CODE_TTL_MINUTES);
        $user->save();

        try {
            Mail::to($user->email)->send(new PasswordResetCode($user->name, $code, self::CODE_TTL_MINUTES));
        } catch (\Exception $e) {
            Log::error('Error sending password reset code email: ' . $e->getMessage());
        }
    }

}
