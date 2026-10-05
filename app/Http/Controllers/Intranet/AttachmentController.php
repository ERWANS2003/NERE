<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment)
    {
        // La SubmissionPolicy::view couvre l'accès aux pièces jointes
        $this->authorize('view', $attachment->submission);

        abort_unless(Storage::disk('private')->exists($attachment->path), 404);

        return Storage::disk('private')->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
