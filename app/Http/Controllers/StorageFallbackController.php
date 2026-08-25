<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StorageFallbackController extends Controller
{
    /**
     * Serves an uploaded file straight from the public disk when the
     * public/storage symlink is missing or broken (doesn't reliably
     * survive being zipped/unzipped or a fresh container build).
     * See routes/web.php 'storage.fallback' for when this is reached.
     */
    public function show(string $path)
    {
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }
}
