<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminUserRegistered;
use App\Models\Alert;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($plan = $request->string('plan')->trim()->value()) {
            $query->where('plan', $plan);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $userList = $users->map(fn (User $user) => $this->formatUser($user));

        return response()->json(['users' => $userList]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json([
            'user' => $this->formatUser($user),
            'contacts' => Contact::where('user_id', $id)->orderBy('priority')->get(),
            'alerts' => Alert::where('user_id', $id)->orderBy('created_at', 'desc')->limit(20)->get(),
            'payments' => Payment::where('user_id', $id)->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^\pL+(\s\pL+)+$/u'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'digits:10'],
            'plan' => ['sometimes', Rule::in(['Demo', 'Básico', 'Premium'])],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
            'username' => ['required_if:role,admin', 'nullable', 'string', 'max:50', 'alpha_dash'],
        ], [
            'name.regex' => 'Ingresa el nombre completo (nombre y apellido), sin números.',
            'phone.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'username.required_if' => 'El nombre de usuario es obligatorio para administradores.',
        ]);

        $role = $validated['role'] ?? 'user';

        // Regular users need a unique email (it's their login identifier). Admins are allowed to
        // share a contact email with other admins — they log in with their unique username instead.
        if ($role !== 'admin' && User::where('email', strtolower($validated['email']))->exists()) {
            return response()->json(['message' => 'Este correo ya está registrado.'], 422);
        }

        if ($role === 'admin' && User::where('username', $validated['username'])->exists()) {
            return response()->json(['message' => 'Ese nombre de usuario ya está en uso.'], 422);
        }

        $plan = $validated['plan'] ?? 'Demo';

        $attributes = [
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => Str::random(40),
            'plan' => $plan,
            'plan_expires_at' => $plan !== 'Demo' ? now()->addDays(30) : null,
            'next_plan' => null,
            'devices' => ['watch' => false, 'alexa' => false],
            'role' => $role,
            'status' => 'pending',
        ];

        // The "username" field has a unique *sparse* index: sparse only skips documents
        // where the field is entirely absent, not documents where it's present-but-null.
        // So for non-admins we must omit the key altogether — never set it to null —
        // otherwise the second non-admin user created collides on username: null.
        if ($role === 'admin') {
            $attributes['username'] = $validated['username'];
        }

        $user = User::create($attributes);

        try {
            Mail::to($user->email)->send(
                new AdminUserRegistered($user->name, $user->email, $user->phone, $plan, $user->username)
            );
        } catch (\Exception $e) {
            Log::error('Error sending admin-user-registered email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Usuario creado correctamente. Se envió un correo con sus datos; deberá activar su cuenta desde el inicio de sesión.',
            'user' => $this->formatUser($user),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120', 'regex:/^\pL+(\s\pL+)+$/u'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'digits:10'],
            'plan' => ['sometimes', Rule::in(['Demo', 'Básico', 'Premium'])],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
            'username' => ['sometimes', 'nullable', 'string', 'max:50', 'alpha_dash'],
            'devices' => ['sometimes', 'array'],
            'devices.watch' => ['sometimes', 'boolean'],
            'devices.alexa' => ['sometimes', 'boolean'],
        ], [
            'name.regex' => 'Ingresa el nombre completo (nombre y apellido), sin números.',
            'phone.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
        ]);

        $targetRole = $validated['role'] ?? $user->role ?? 'user';

        if (isset($validated['email'])) {
            $validated['email'] = strtolower($validated['email']);
            // Admins are allowed to share a contact email with other admins.
            if ($targetRole !== 'admin' && User::where('email', $validated['email'])->where('_id', '!=', $id)->exists()) {
                return response()->json(['message' => 'Este correo ya está en uso por otro usuario.'], 422);
            }
        }

        if (! empty($validated['username']) && User::where('username', $validated['username'])->where('_id', '!=', $id)->exists()) {
            return response()->json(['message' => 'Ese nombre de usuario ya está en uso.'], 422);
        }

        if (array_key_exists('role', $validated) && $validated['role'] !== 'admin' && $user->role === 'admin' && (string) $request->user()->_id === $id) {
            return response()->json(['message' => 'No puedes quitarte el rol de administrador a ti mismo.'], 422);
        }

        if (isset($validated['plan']) && $validated['plan'] !== $user->plan) {
            $user->plan_expires_at = $validated['plan'] !== 'Demo' ? now()->addDays(30) : null;
            $user->next_plan = null;
        }

        if (isset($validated['devices'])) {
            $devices = $user->devices ?? ['watch' => false, 'alexa' => false];
            $deviceNames = $user->device_names ?? ['watch' => null, 'alexa' => null];
            foreach (['watch', 'alexa'] as $key) {
                if (array_key_exists($key, $validated['devices'])) {
                    $devices[$key] = $validated['devices'][$key];
                    if (! $devices[$key]) {
                        $deviceNames[$key] = null;
                    }
                }
            }
            $user->devices = $devices;
            $user->device_names = $deviceNames;
            unset($validated['devices']);
        }

        $user->fill($validated);
        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if ((string) $request->user()->_id === $id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta de administrador.'], 422);
        }

        $user = User::findOrFail($id);

        Contact::where('user_id', $id)->delete();
        Alert::where('user_id', $id)->delete();
        Payment::where('user_id', $id)->delete();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Usuario y sus datos asociados fueron eliminados.']);
    }

    public function resetPassword(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::findOrFail($id);
        $user->password = $validated['password'];
        $user->status = 'active';
        $user->save();
        $user->tokens()->delete();

        return response()->json(['message' => "Contraseña de {$user->email} actualizada correctamente."]);
    }

    public function destroyContact(Request $request, string $userId, string $contactId): JsonResponse
    {
        $contact = Contact::where('_id', $contactId)->where('user_id', $userId)->firstOrFail();
        $contact->delete();

        return response()->json(['message' => 'Contacto eliminado correctamente.']);
    }

    public function revertPlan(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->plan = 'Demo';
        $user->plan_expires_at = null;
        $user->next_plan = null;
        $user->save();

        return response()->json([
            'message' => "Plan de {$user->email} revertido a Demo.",
            'user' => $this->formatUser($user),
        ]);
    }

    private function formatUser(User $user): array
    {
        $id = (string) $user->_id;

        return [
            'id' => $id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username ?? null,
            'phone' => $user->phone,
            'plan' => $user->plan ?? 'Demo',
            'plan_expires_at' => $user->plan_expires_at ? $user->plan_expires_at->toIso8601String() : null,
            'next_plan' => $user->next_plan,
            'devices' => $user->devices ?? ['watch' => false, 'alexa' => false],
            'device_names' => $user->device_names ?? ['watch' => null, 'alexa' => null],
            'role' => $user->role ?? 'user',
            'status' => $user->status ?? 'active',
            'created_at' => $user->created_at?->toIso8601String(),
            'contacts_count' => Contact::where('user_id', $id)->count(),
            'alerts_count' => Alert::where('user_id', $id)->count(),
        ];
    }
}
