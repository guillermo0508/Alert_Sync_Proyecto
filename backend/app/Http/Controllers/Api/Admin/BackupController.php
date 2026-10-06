<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BackupController extends Controller
{
    /**
     * Collections that hold real project data. personal_access_tokens is deliberately
     * excluded: those are temporary session credentials, not business data, and backing
     * them up would just hand out currently-valid bearer tokens.
     */
    private const COLLECTIONS = ['users', 'contacts', 'alerts', 'payments', 'plans', 'site_content'];

    public function create(Request $request): BinaryFileResponse
    {
        $database = DB::connection('mongodb')->getDatabase();

        $zipPath = tempnam(sys_get_temp_dir(), 'alertsync_backup_');
        rename($zipPath, $zipPath .= '.zip');

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $summary = [];

        foreach (self::COLLECTIONS as $collectionName) {
            $documents = [];

            foreach ($database->selectCollection($collectionName)->find() as $document) {
                $documents[] = \MongoDB\BSON\toJSON(\MongoDB\BSON\fromPHP($document));
            }

            $zip->addFromString("{$collectionName}.json", '[' . implode(',', $documents) . ']');
            $summary[$collectionName] = count($documents);
        }

        $zip->addFromString('manifest.json', json_encode([
            'project' => 'ALERTSYNC',
            'database' => config('database.connections.mongodb.database'),
            'generated_at' => now()->toIso8601String(),
            'generated_by' => $request->user()->email,
            'excluded_collections' => ['personal_access_tokens'],
            'document_counts' => $summary,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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
