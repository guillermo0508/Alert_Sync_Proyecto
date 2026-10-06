<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $usersByPlan = [
            'Demo' => User::where(fn ($q) => $q->whereNull('plan')->orWhere('plan', 'Demo'))->count(),
            'Básico' => User::where('plan', 'Básico')->count(),
            'Premium' => User::where('plan', 'Premium')->count(),
        ];

        $revenue = (float) Payment::where('status', 'completed')->sum('amount');
        $refunded = (float) Payment::where('status', 'refunded')->sum('amount');

        $last24h = now()->subDay();

        return response()->json([
            'stats' => [
                'total_users' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'users_by_plan' => $usersByPlan,
                'total_contacts' => Contact::count(),
                'total_alerts' => Alert::count(),
                'alerts_last_24h' => Alert::where('created_at', '>=', $last24h)->count(),
                'total_payments' => Payment::count(),
                'revenue_total' => $revenue,
                'revenue_refunded' => $refunded,
                'revenue_net' => $revenue - $refunded,
            ],
            'recent_alerts' => Alert::orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_payments' => Payment::orderBy('created_at', 'desc')->limit(5)->get(),
            'alerts_by_day' => $this->alertsByDay(),
            'revenue_by_month' => $this->revenueByMonth(),
        ]);
    }

    private function alertsByDay(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $counts = array_fill(0, $days, 0);
        $labels = [];
        for ($i = 0; $i < $days; $i++) {
            $labels[$i] = $start->copy()->addDays($i)->format('Y-m-d');
        }
        $indexByDate = array_flip($labels);

        Alert::where('created_at', '>=', $start)->get(['created_at'])->each(function ($alert) use (&$counts, $indexByDate) {
            $day = $alert->created_at?->format('Y-m-d');
            if ($day !== null && isset($indexByDate[$day])) {
                $counts[$indexByDate[$day]]++;
            }
        });

        return collect($labels)->map(fn ($date, $i) => [
            'date' => $date,
            'count' => $counts[$i],
        ])->values()->all();
    }

    private function revenueByMonth(int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $totals = array_fill(0, $months, 0.0);
        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[$i] = $start->copy()->addMonths($i)->format('Y-m');
        }
        $indexByMonth = array_flip($labels);

        Payment::where('status', 'completed')->where('created_at', '>=', $start)
            ->get(['created_at', 'amount'])
            ->each(function ($payment) use (&$totals, $indexByMonth) {
                $month = $payment->created_at?->format('Y-m');
                if ($month !== null && isset($indexByMonth[$month])) {
                    $totals[$indexByMonth[$month]] += (float) $payment->amount;
                }
            });

        return collect($labels)->map(fn ($month, $i) => [
            'month' => $month,
            'total' => $totals[$i],
        ])->values()->all();
    }
}
