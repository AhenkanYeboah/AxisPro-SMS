<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StorageFallbackController extends Controller
{
    public function show(string $path)
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        // Fix for Render ephemeral disk - serve default logo when branding is missing
        if (str_starts_with($path, 'branding/')) {
            $fallback = public_path('images/axispro-logo.webp');
            if (file_exists($fallback)) {
                return response()->file($fallback);
            }
        }

        abort(404);
    }
}
