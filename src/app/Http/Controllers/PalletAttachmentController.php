<?php

namespace App\Http\Controllers;

use App\Models\Pallet;
use App\Models\PalletAttachment;
use App\Models\StoredFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PalletAttachmentController extends Controller
{
    public function store(Request $request, Pallet $pallet)
    {
        $request->validate([
            'attachments' => ['required', 'array', 'max:20'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $pallet->addAttachments($request->file('attachments'));

        return back()->with('ok', 'Fichier(s) ajouté(s).');
    }

    /** Affichage inline (navigateur / visionneuse PDF). */
    public function show(Pallet $pallet, PalletAttachment $attachment): StreamedResponse
    {
        return $this->respond($pallet, $attachment, inline: true);
    }

    public function download(Pallet $pallet, PalletAttachment $attachment): StreamedResponse
    {
        return $this->respond($pallet, $attachment, inline: false);
    }

    public function destroy(Pallet $pallet, PalletAttachment $attachment)
    {
        abort_unless($attachment->pallet_id === $pallet->id, 404);
        $attachment->delete();

        return back()->with('ok', 'Fichier supprimé.');
    }

    private function respond(Pallet $pallet, PalletAttachment $attachment, bool $inline): StreamedResponse
    {
        abort_unless($attachment->pallet_id === $pallet->id, 404);

        if ($attachment->disk === StoredFile::DATABASE_DISK) {
            $storedFile = StoredFile::query()->where('path', $attachment->path)->firstOrFail();
            $content = base64_decode($storedFile->content_base64, true);
            abort_if($content === false, 500);

            return response()->streamDownload(
                static function () use ($content): void {
                    echo $content;
                },
                $attachment->original_name,
                ['Content-Type' => $attachment->mime_type ?: ($storedFile->mime_type ?: 'application/octet-stream')],
                $inline ? 'inline' : 'attachment',
            );
        }

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        $headers = ['Content-Type' => $attachment->mime_type ?: ($disk->mimeType($attachment->path) ?: 'application/octet-stream')];

        return $inline
            ? $disk->response($attachment->path, $attachment->original_name, $headers)
            : $disk->download($attachment->path, $attachment->original_name, $headers);
    }
}
