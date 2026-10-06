<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthUser;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
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
        $request->validate([
            'plan_id' => ['required', 'in:basico,premium'],
            'card_number' => ['required', 'string', 'min:16', 'max:19'],
            'card_expiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{2}$/'],
            'card_cvv' => ['required', 'string', 'digits_between:3,4'],
            'card_name' => ['required', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $targetPlan = $request->plan_id === 'basico' ? 'Básico' : 'Premium';

        if ($user->plan === $targetPlan) {
            return response()->json(['message' => 'Ya cuentas con este plan.'], 400);
        }

        // Apply immediately
        $user->plan = $targetPlan;
        $user->plan_expires_at = now()->addDays(30);
        $user->next_plan = null;

        // Send payment receipt email
        $lastFour = substr(str_replace(' ', '', $request->card_number), -4);
        $user->payment_card_last_four = $lastFour;
        $user->payment_card_name = $request->card_name;
        $user->payment_card_expiry = $request->card_expiry;
        $user->auto_renew = true;
        $user->save();
        $planAmount = Plan::getOrDefault($request->plan_id)->price_amount;
        $planPrice = "\${$planAmount}/mes";
        $formattedExpiresAt = $user->plan_expires_at->format('d/m/Y');

        Payment::create([
            'user_id' => (string) $user->_id,
            'plan' => $targetPlan,
            'amount' => $planAmount,
            'currency' => 'MXN',
            'card_last_four' => $lastFour,
            'card_name' => $request->card_name,
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
