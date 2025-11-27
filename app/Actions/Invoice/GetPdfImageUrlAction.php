<?php

namespace App\Actions\Invoice;

use Illuminate\Support\Facades\Storage;

class GetPdfImageUrlAction
{
    /**
     * Get a URL that the PDF service can access.
     * In local environment with Docker, we need to use the container name
     * instead of localhost/stackrats.local so the PDF service can resolve it.
     */
    public function handle(string $disk, string $path): string
    {
        // In local environment, always use Docker URL for PDF generation
        // since the PDF service runs in a separate container
        if (app()->environment('local')) {
            $dockerUrl = config('app.docker_url').'/storage';
            $url = "{$dockerUrl}/{$path}";

            return $url;
        }

        /** @var \Illuminate\Filesystem\FilesystemManager $storage */
        $storage = Storage::disk($disk);

        // For non-local contexts, use normal Storage URL
        return $storage->url($path);
    }
}
