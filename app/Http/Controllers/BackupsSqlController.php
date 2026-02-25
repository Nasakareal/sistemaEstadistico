<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupsSqlController extends Controller
{
    public function index()
    {
        $dir = 'backups_sql';

        if (!Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }

        $files = collect(Storage::disk('local')->files($dir))
            ->filter(function ($path) {
                $name = basename($path);
                return (bool) preg_match('/^[A-Za-z0-9._-]+\.sql(\.gz)?$/', $name);
            })
            ->map(function ($path) {
                $disk = Storage::disk('local');
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => $disk->size($path),
                    'last_modified' => $disk->lastModified($path),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return view('admin.settings.backups_sql.index', compact('files'));
    }

    public function download(string $file)
    {
        if (!preg_match('/^[A-Za-z0-9._-]+\.sql(\.gz)?$/', $file)) {
            abort(404);
        }

        $path = 'backups_sql/' . $file;

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('local')->path($path);

        return response()->download($absolutePath, $file, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
