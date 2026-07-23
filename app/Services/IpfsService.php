<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class IpfsService
{
    public function isEnabled(): bool
    {
        return (bool) config('autochain.ipfs.enabled');
    }

    public function pinFile(string $filePath, string $disk = 'documents'): ?string
    {
        if (! Storage::disk($disk)->exists($filePath)) {
            return null;
        }

        if (! $this->isEnabled()) {
            return 'sim-'.substr(hash('sha256', $filePath), 0, 46);
        }

        $apiUrl = rtrim(config('autochain.ipfs.api_url'), '/');
        $fullPath = Storage::disk($disk)->path($filePath);

        $response = Http::timeout(30)
            ->attach('file', file_get_contents($fullPath), basename($filePath))
            ->post("{$apiUrl}/api/v0/add");

        if (! $response->successful()) {
            return null;
        }

        $lines = array_filter(explode("\n", trim($response->body())));
        $last = json_decode(end($lines) ?: '{}', true);

        return $last['Hash'] ?? null;
    }

    public function publicUrl(?string $cid): ?string
    {
        if (! $cid) {
            return null;
        }

        return rtrim(config('autochain.ipfs.gateway'), '/').'/'.$cid;
    }
}
