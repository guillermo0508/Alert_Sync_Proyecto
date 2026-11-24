<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Lecturas del reloj y del celular (signos vitales, ubicación, batería).
 * Solo el propio usuario puede enviarlas y consultarlas.
 */
class DeviceReadingController extends Controller
{
    /** Rangos de historial permitidos (horas). */
    private const RANGES = ['6h' => 6, '24h' => 24, '7d' => 168];

    /** Las lecturas más viejas que esto se borran al guardar una nueva. */
    private const RETENTION_DAYS = 30;

    /** Un dispositivo se considera "en línea" si reportó en estos minutos. */
    private const ONLINE_MINUTES = 10;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device' => ['required', 'in:watch,mobile'],
            'heart_rate' => ['nullable', 'integer', 'between:20,250'],
            'spo2' => ['nullable', 'integer', 'between:50,100'],
            'steps' => ['nullable', 'integer', 'between:0,200000'],
            'skin_temp' => ['nullable', 'numeric', 'between:20,45'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'integer', 'between:0,65535'],
            'battery' => ['nullable', 'integer', 'between:0,100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $hasData = collect($data)->except(['device', 'recorded_at'])->filter(fn ($v) => $v !== null)->isNotEmpty();
        if (! $hasData) {
            return response()->json(['message' => 'La lectura no contiene datos.'], 422);
        }

        // Una fecha futura (reloj del dispositivo adelantado) se toma como "ahora".
        $recordedAt = isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : now();
        if ($recordedAt->isFuture()) {
            $recordedAt = now();
        }

        $userId = $request->user()->id;
        $reading = DeviceReading::create([...$data, 'user_id' => $userId, 'recorded_at' => $recordedAt]);

        DeviceReading::where('user_id', $userId)
            ->where('recorded_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->delete();

        return response()->json(['reading' => $reading], 201);
    }

    /** Última lectura de cada dato y estado de cada dispositivo. */
    public function latest(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $base = fn () => DeviceReading::where('user_id', $userId)->orderByDesc('recorded_at');

        $lastWith = function (string $column) use ($base) {
            $r = $base()->whereNotNull($column)->first();

            return $r ? ['value' => $r->{$column}, 'device' => $r->device, 'recorded_at' => $r->recorded_at] : null;
        };

        $location = $base()->whereNotNull('latitude')->first();

        $devices = [];
        foreach (['watch', 'mobile'] as $device) {
            $r = $base()->where('device', $device)->first();
            $devices[$device] = [
                'last_seen' => $r?->recorded_at,
                'online' => $r !== null && $r->recorded_at->gt(now()->subMinutes(self::ONLINE_MINUTES)),
                'battery' => $base()->where('device', $device)->whereNotNull('battery')->value('battery'),
            ];
        }

        return response()->json([
            'vitals' => [
                'heart_rate' => $lastWith('heart_rate'),
                'spo2' => $lastWith('spo2'),
                'steps' => $lastWith('steps'),
                'skin_temp' => $lastWith('skin_temp'),
            ],
            'location' => $location ? [
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'accuracy' => $location->accuracy,
                'device' => $location->device,
                'recorded_at' => $location->recorded_at,
            ] : null,
            'devices' => $devices,
        ]);
    }

    /** Historial para las gráficas (solo plan Premium). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->isPremium($user->plan)) {
            return response()->json([
                'locked' => true,
                'message' => 'El historial de signos vitales está disponible en el plan Premium.',
                'readings' => [],
            ]);
        }

        $range = $request->query('range', '24h');
        $hours = self::RANGES[$range] ?? 24;

        $readings = DeviceReading::where('user_id', $user->id)
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->limit(2000)
            ->get(['id', 'device', 'heart_rate', 'spo2', 'steps', 'skin_temp',
                'latitude', 'longitude', 'battery', 'recorded_at']);

        return response()->json(['locked' => false, 'range' => $range, 'readings' => $readings]);
    }

    private function isPremium(?string $plan): bool
    {
        return $plan !== null && mb_strtolower($plan) === 'premium';
    }
}
