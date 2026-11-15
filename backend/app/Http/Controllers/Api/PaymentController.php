<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Historial de pagos del usuario autenticado (sólo sus propios pagos).
 */
class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['id', 'plan', 'amount', 'currency', 'card_last_four', 'status', 'note', 'created_at']);

        return response()->json(['payments' => $payments]);
    }
}
