<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ficha médica del usuario (tipo de sangre y alergias). Se envía en el correo SOS.
 */
class MedicalController extends Controller
{
    use FormatsAuthUser;

    public const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blood_type' => ['nullable', 'string', 'in:' . implode(',', self::BLOOD_TYPES)],
            'allergies' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $user->blood_type = $validated['blood_type'] ?? null;
        $user->allergies = isset($validated['allergies']) ? trim($validated['allergies']) : null;
        $user->save();

        return response()->json([
            'message' => 'Ficha médica actualizada.',
            'user' => $this->formatUser($user),
        ]);
    }
}
