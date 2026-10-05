<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Attachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /** Téléchargement sécurisé depuis le disque privé. */
    public function download(Attachment $attachment)
    {
        $user = Auth::user();
        $sub  = $attachment->submission;

        // Accès : demandeur, assigné, technicien/directeur du département, super admin
        $dept = $sub->form->service->department;
        $isMember = $dept->users()->where('users.id', $user->id)
            ->wherePivotIn('role', ['technician', 'director'])
            ->exists();

        $canAccess = $user->is_super_admin
            || $sub->requester_id === $user->id
            || $sub->assignee_id  === $user->id
            || $isMember;

        abort_unless($canAccess, 403);
        abort_unless(Storage::disk('private')->exists($attachment->path), 404);

        return Storage::disk('private')->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
