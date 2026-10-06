<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use FormatsAuthUser;

    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->formatUser($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $email = strtolower($validated['email']);
        $existing = User::where('email', $email)->first();
        if ($existing && (string) $existing->_id !== (string) $user->_id) {
            throw ValidationException::withMessages([
                'email' => ['Este correo ya está en uso por otra cuenta.'],
            ]);
        }

        $user->name = $validated['name'];
        $user->email = $email;
        $user->phone = $validated['phone'] ?? null;
        $user->save();

        return response()->json([
            'message' => 'Tus datos personales se actualizaron correctamente.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function updatePaymentMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'min:16', 'max:19'],
            'card_expiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{2}$/'],
            'card_cvv' => ['required', 'string', 'digits_between:3,4'],
            'card_name' => ['required', 'string', 'max:120'],
            'auto_renew' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $lastFour = substr(str_replace(' ', '', $validated['card_number']), -4);

        $user->payment_card_last_four = $lastFour;
        $user->payment_card_name = $validated['card_name'];
        $user->payment_card_expiry = $validated['card_expiry'];
        $user->auto_renew = array_key_exists('auto_renew', $validated)
            ? (bool) $validated['auto_renew']
            : true;
        $user->save();

        return response()->json([
            'message' => 'Forma de pago guardada. Se usará para el cobro automático de tu suscripción.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function updateAutoRenew(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auto_renew' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->auto_renew = $validated['auto_renew'];
        $user->save();

        return response()->json([
            'message' => $validated['auto_renew']
                ? 'Cobro automático activado.'
                : 'Cobro automático desactivado.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function updateDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device' => ['required', 'string', 'in:watch,alexa'],
            'linked' => ['required', 'boolean'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $devices = $user->devices ?? ['watch' => false, 'alexa' => false];
        $deviceNames = $user->device_names ?? ['watch' => null, 'alexa' => null];

        $devices[$validated['device']] = $validated['linked'];
        $deviceNames[$validated['device']] = $validated['linked'] ? ($validated['name'] ?? null) : null;

        $user->devices = $devices;
        $user->device_names = $deviceNames;
        $user->save();

        return response()->json([
            'message' => $validated['linked']
                ? 'Dispositivo vinculado correctamente.'
                : 'Dispositivo desvinculado.',
            'user' => $this->formatUser($user),
        ]);
    }
}
