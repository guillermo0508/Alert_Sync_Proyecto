<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthUser;
use App\Http\Controllers\Controller;
use App\Models\PaymentCard;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tarjetas guardadas del usuario (agregar, elegir predeterminada, quitar).
 */
class PaymentCardController extends Controller
{
    use FormatsAuthUser;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->importLegacyCard($user);

        return response()->json(['cards' => $this->cards($user)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'min:16', 'max:19'],
            'card_expiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{2}$/'],
            'card_cvv' => ['required', 'string', 'digits_between:3,4'],
            'card_name' => ['required', 'string', 'max:120'],
            'make_default' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $this->importLegacyCard($user);
        $number = preg_replace('/\D/', '', $validated['card_number']);
        $isFirst = ! PaymentCard::where('user_id', $user->id)->exists();

        $card = PaymentCard::create([
            'user_id' => $user->id,
            'brand' => $this->detectBrand($number),
            'last_four' => substr($number, -4),
            'card_name' => $validated['card_name'],
            'card_expiry' => $validated['card_expiry'],
            'is_default' => false,
        ]);

        if ($isFirst || ($validated['make_default'] ?? true)) {
            $this->makeDefault($user, $card);
        }

        return response()->json([
            'message' => 'Tarjeta agregada.',
            'cards' => $this->cards($user),
            'user' => $this->formatUser($user->fresh()),
        ], 201);
    }

    public function setDefault(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $card = PaymentCard::where('user_id', $user->id)->whereKey($id)->firstOrFail();
        $this->makeDefault($user, $card);

        return response()->json([
            'message' => 'Tarjeta predeterminada actualizada.',
            'cards' => $this->cards($user),
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $card = PaymentCard::where('user_id', $user->id)->whereKey($id)->firstOrFail();
        $wasDefault = $card->is_default;
        $card->delete();

        if ($wasDefault) {
            $next = PaymentCard::where('user_id', $user->id)->latest()->first();
            if ($next) {
                $this->makeDefault($user, $next);
            } else {
                $user->payment_card_last_four = null;
                $user->payment_card_name = null;
                $user->payment_card_expiry = null;
                $user->save();
            }
        }

        return response()->json([
            'message' => 'Tarjeta eliminada.',
            'cards' => $this->cards($user),
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    // -----------------------------------------------------------------

    private function makeDefault(User $user, PaymentCard $card): void
    {
        $card->makeDefaultFor($user);
    }

    /** Si el usuario ya tenía una tarjeta (guardada antes de esta función), se agrega a la lista. */
    private function importLegacyCard(User $user): void
    {
        if (! $user->payment_card_last_four || PaymentCard::where('user_id', $user->id)->exists()) {
            return;
        }
        PaymentCard::create([
            'user_id' => $user->id,
            'brand' => null,
            'last_four' => $user->payment_card_last_four,
            'card_name' => $user->payment_card_name ?? $user->name,
            'card_expiry' => $user->payment_card_expiry ?? '--/--',
            'is_default' => true,
        ]);
    }

    private function cards(User $user): array
    {
        return PaymentCard::where('user_id', $user->id)
            ->orderByDesc('is_default')->orderByDesc('created_at')
            ->get(['id', 'brand', 'last_four', 'card_name', 'card_expiry', 'is_default', 'created_at'])
            ->toArray();
    }

    private function detectBrand(string $number): string
    {
        return PaymentCard::detectBrand($number);
    }
}
