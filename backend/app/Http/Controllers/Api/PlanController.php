<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthUser;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentCard;
use App\Models\Plan;
use App\Rules\CardExpiry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    use FormatsAuthUser;

    public function index(): JsonResponse
    {
        return response()->json(['plans' => $this->formattedPlans()]);
    }

    private function formattedPlans(): array
    {
        return collect(['basico', 'premium'])->map(function (string $planId) {
            $plan = Plan::getOrDefault($planId);

            return [
                'id' => $plan->plan_id,
                'name' => $plan->name,
                'price' => '$' . $plan->price_amount . '/mes',
                'features' => $plan->features,
                'max_contacts' => $plan->max_contacts,
            ];
        })->values()->all();
    }

    public function adminUpdate(Request $request, string $planId): JsonResponse
    {
        if (! in_array($planId, ['basico', 'premium'], true)) {
            return response()->json(['message' => 'Plan no válido.'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'price_amount' => ['required', 'integer', 'min:1', 'max:100000'],
            'features' => ['required', 'array', 'min:1', 'max:10'],
            'features.*' => ['required', 'string', 'max:150'],
            'max_contacts' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $plan = Plan::where('plan_id', $planId)->first() ?? new Plan(['plan_id' => $planId]);
        $plan->fill($validated);
        $plan->save();

        return response()->json([
            'message' => 'Plan actualizado correctamente.',
            'plans' => $this->formattedPlans(),
        ]);
    }

    public function change(Request $request): JsonResponse
    {
        // Se aceptan espacios en el número ("4242 4242 …"): se quitan antes de validar.
        if ($request->filled('card_number')) {
            $request->merge(['card_number' => preg_replace('/\s+/', '', (string) $request->input('card_number'))]);
        }
        // Se paga con una tarjeta guardada (card_id) o con una nueva (que se guarda en la lista).
        $request->validate([
            'plan_id' => ['required', 'in:basico,premium'],
            'card_id' => ['nullable', 'integer'],
            'card_number' => ['required_without:card_id', 'nullable', 'digits:16'],
            'card_expiry' => ['required_without:card_id', 'nullable', 'string', new CardExpiry],
            'card_cvv' => ['required_without:card_id', 'nullable', 'digits:3'],
            'card_name' => ['required_without:card_id', 'nullable', 'string', 'max:120'],
            'make_default' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $targetPlan = $request->plan_id === 'basico' ? 'Básico' : 'Premium';

        if ($user->plan === $targetPlan) {
            return response()->json(['message' => 'Ya cuentas con este plan.'], 400);
        }

        if ($request->filled('card_id')) {
            $card = PaymentCard::where('user_id', $user->id)->whereKey($request->card_id)->first();
            if (! $card) {
                return response()->json(['message' => 'La tarjeta elegida ya no está guardada. Elige otra.'], 422);
            }
        } else {
            $number = preg_replace('/\D/', '', (string) $request->card_number);
            $isFirst = ! PaymentCard::where('user_id', $user->id)->exists();
            $card = PaymentCard::create([
                'user_id' => $user->id,
                'brand' => PaymentCard::detectBrand($number),
                'last_four' => substr($number, -4),
                'card_name' => $request->card_name,
                'card_expiry' => $request->card_expiry,
                'is_default' => false,
            ]);
            // Igual que antes: si no se indica otra cosa, la tarjeta nueva queda como principal.
            if ($isFirst || $request->boolean('make_default', true)) {
                $card->makeDefaultFor($user);
            }
        }
        $lastFour = $card->last_four;

        // Se aplica de inmediato
        $user->refresh();
        $user->plan = $targetPlan;
        $user->plan_expires_at = now()->addDays(30);
        $user->next_plan = null;
        $user->auto_renew = true;
        $user->save();
        $planAmount = Plan::getOrDefault($request->plan_id)->price_amount;
        $planPrice = "\${$planAmount}/mes";
        $formattedExpiresAt = $user->plan_expires_at->format('d/m/Y');

        Payment::create([
            'user_id' => $user->id,
            'plan' => $targetPlan,
            'amount' => $planAmount,
            'currency' => 'MXN',
            'card_last_four' => $lastFour,
            'card_name' => $card->card_name,
            'status' => 'completed',
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\PaymentReceipt(
                    $user->name,
                    $targetPlan,
                    $planPrice,
                    $lastFour,
                    $formattedExpiresAt
                )
            );
        } catch (\Exception $e) {
            // Log the error but don't crash the response if mail setup is not fully configured
            \Illuminate\Support\Facades\Log::error('Error sending payment receipt: ' . $e->getMessage());
        }

        return response()->json([
            'message' => "Plan {$targetPlan} contratado y activado exitosamente. Se ha enviado un recibo a tu correo.",
            'user' => $this->formatUser($user),
        ]);
    }
}
