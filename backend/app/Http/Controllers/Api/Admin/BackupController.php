<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use RuntimeException;
use ZipArchive;

class BackupController extends Controller
{
    private const TABLES = ['users', 'contacts', 'alerts', 'payments', 'plans', 'site_contents'];

    public function create(Request $request): BinaryFileResponse
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'alertsync_backup_');
        if ($zipPath === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal de respaldo.');
        }

        $temporaryPath = $zipPath;
        $zipPath .= '.zip';
        if (! rename($temporaryPath, $zipPath)) {
            throw new RuntimeException('No se pudo preparar el archivo ZIP de respaldo.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo ZIP de respaldo.');
        }

        $summary = [];

        foreach (self::TABLES as $tableName) {
            $rows = DB::table($tableName)->orderBy('id')->get()->toArray();
            $contents = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (! $zip->addFromString("{$tableName}.json", $contents)) {
                throw new RuntimeException("No se pudo agregar la tabla {$tableName} al respaldo.");
            }
            $summary[$tableName] = count($rows);
        }

        $manifest = json_encode([
            'project' => 'ALERTSYNC',
            'database' => config('database.connections.' . config('database.default') . '.database'),
            'generated_at' => now()->toIso8601String(),
            'generated_by' => $request->user()->email,
            'excluded_tables' => ['personal_access_tokens'],
            'row_counts' => $summary,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (! $zip->addFromString('manifest.json', $manifest)) {
            throw new RuntimeException('No se pudo agregar el manifiesto al respaldo.');
        }

        $zip->close();

        Log::info('Admin database backup generated', [
            'admin' => $request->user()->email,
            'document_counts' => $summary,
        ]);

        $filename = 'alertsync-backup-' . now()->format('Y-m-d_His') . '.zip';

        return response()->download($zipPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
