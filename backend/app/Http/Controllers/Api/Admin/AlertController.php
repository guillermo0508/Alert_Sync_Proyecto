<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Alert::query();

        if ($source = $request->string('source')->trim()->value()) {
            $query->where('source', $source);
        }

        $alerts = $query->orderBy('created_at', 'desc')->limit(200)->get();

        $userIds = $alerts->pluck('user_id')->unique()->values()->all();
        $users = User::whereIn('_id', $userIds)->get()->keyBy(fn (User $u) => (string) $u->_id);

        $formatted = $alerts->map(function (Alert $alert) use ($users) {
            $user = $users->get($alert->user_id);

            return [
                'id' => (string) $alert->_id,
                'user_id' => $alert->user_id,
                'user_name' => $user?->name ?? 'Usuario eliminado',
                'user_email' => $user?->email ?? '—',
                'source' => $alert->source,
                'status' => $alert->status,
                'message' => $alert->message,
                'latitude' => $alert->latitude,
                'longitude' => $alert->longitude,
                'contacts_notified' => $alert->contacts_notified,
                'created_at' => $alert->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['alerts' => $formatted]);
    }

    public function destroy(string $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);
        $alert->delete();

        return response()->json(['message' => 'Alerta eliminada correctamente.']);
    }
}
