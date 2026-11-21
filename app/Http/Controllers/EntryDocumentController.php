<?php

namespace App\Http\Controllers;

use App\Models\EntryDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a competition document from private storage to the participant
 * who uploaded it, the admin, or a jury member assigned to the competition.
 */
class EntryDocumentController extends Controller
{
    public function __invoke(Request $request, EntryDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document->entry);

        $disk = Storage::disk(EntryDocument::DISK);
        abort_unless($disk->exists($document->path), 404);

        $headers = [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        // Only PDFs and raster images are shown inline; everything else downloads.
        $disposition = $document->isPreviewable() && ! $request->boolean('download') ? 'inline' : 'attachment';

        return $disk->response($document->path, $document->original_name, $headers, $disposition);
    }
}
