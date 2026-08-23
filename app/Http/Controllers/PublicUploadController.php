<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class PublicUploadController extends Controller
{
    public function __invoke(string $path)
    {
        abort_if($path === '' || str_contains($path, '..') || str_contains($path, '\\'), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path));
    }
}
