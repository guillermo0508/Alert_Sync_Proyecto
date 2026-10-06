<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = Alert::where('user_id', (string) $request->user()->_id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['alerts' => $alerts]);
    }

    public function trigger(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'in:watch,alexa,dashboard,dashboard-premium,web,simulator'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $userId = (string) $request->user()->_id;
        $contacts = Contact::where('user_id', $userId)->orderBy('priority')->get();

        $notified = $contacts->map(fn (Contact $contact) => [
            'id' => (string) $contact->_id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'channels' => array_values(array_filter([
                $contact->notify_sms ? 'sms' : null,
                $contact->notify_call ? 'call' : null,
                $contact->notify_email ? 'email' : null,
            ])),
        ])->all();

        $alert = Alert::create([
            'user_id' => $userId,
            'source' => $validated['source'],
            'status' => 'sent',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'message' => $validated['message'] ?? 'Alerta SOS activada — se requiere ayuda inmediata.',
            'contacts_notified' => $notified,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        return response()->json([
            'message' => 'Alerta SOS enviada correctamente.',
            'alert' => $alert,
            'contacts_count' => count($notified),
        ], 201);
    }
}
