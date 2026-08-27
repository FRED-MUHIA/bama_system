<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class PublicUpload
{
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $diskPath = str_starts_with($path, 'storage/')
            ? substr($path, strlen('storage/'))
            : $path;

        if (Storage::disk('public')->exists($diskPath)) {
            return Route::has('uploads.public')
                ? route('uploads.public', ['path' => $diskPath], false)
                : Storage::url($diskPath);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        $storagePath = 'storage/'.$path;

        return file_exists(public_path($storagePath)) ? asset($storagePath) : null;
    }

    public static function filePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = ltrim($path, '/');
        $diskPath = str_starts_with($path, 'storage/')
            ? substr($path, strlen('storage/'))
            : $path;

        if (Storage::disk('public')->exists($diskPath)) {
            return Storage::disk('public')->path($diskPath);
        }

        $publicPath = public_path($path);

        if (file_exists($publicPath)) {
            return $publicPath;
        }

        $storagePath = public_path('storage/'.$path);

        return file_exists($storagePath) ? $storagePath : null;
    }
}
