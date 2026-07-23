<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function store(UploadedFile $file, int $vehicleId): array
    {
        $disk = config('autochain.documents.disk', 'documents');
        $path = $file->store("vehicles/{$vehicleId}", $disk);
        $hash = hash_file('sha256', Storage::disk($disk)->path($path));

        return [
            'file_path' => $path,
            'file_hash' => $hash,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function verifyIntegrity(string $filePath, string $expectedHash): bool
    {
        $disk = config('autochain.documents.disk', 'documents');

        if (! Storage::disk($disk)->exists($filePath)) {
            return false;
        }

        $actualHash = hash_file('sha256', Storage::disk($disk)->path($filePath));

        return hash_equals($expectedHash, $actualHash);
    }

    public function delete(string $filePath): void
    {
        $disk = config('autochain.documents.disk', 'documents');
        Storage::disk($disk)->delete($filePath);
    }
}
