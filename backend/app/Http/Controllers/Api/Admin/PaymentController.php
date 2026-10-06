<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query();

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        if ($plan = $request->string('plan')->trim()->value()) {
            $query->where('plan', $plan);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        $userIds = $payments->pluck('user_id')->unique()->values()->all();
        $users = User::whereIn('_id', $userIds)->get()->keyBy(fn (User $u) => (string) $u->_id);

        $formatted = $payments->map(function (Payment $payment) use ($users) {
            $user = $users->get($payment->user_id);

            return [
                'id' => (string) $payment->_id,
                'user_id' => $payment->user_id,
                'user_name' => $user?->name ?? 'Usuario eliminado',
                'user_email' => $user?->email ?? '—',
                'plan' => $payment->plan,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'card_last_four' => $payment->card_last_four,
                'card_name' => $payment->card_name,
                'status' => $payment->status,
                'note' => $payment->note,
                'created_at' => $payment->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'payments' => $formatted,
            'total_amount' => $payments->where('status', 'completed')->sum('amount'),
        ]);
    }

    public function refund(Request $request, string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'refunded') {
            return response()->json(['message' => 'Este pago ya fue reembolsado.'], 422);
        }

        $payment->status = 'refunded';
        $payment->note = $request->string('note')->trim()->value() ?: 'Reembolsado por administrador';
        $payment->save();

        return response()->json(['message' => 'Pago marcado como reembolsado.']);
    }

    public function destroy(string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Registro de pago eliminado.']);
    }
}
