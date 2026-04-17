<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\User;

class AttachmentPolicy {

    /**
     * Super admins bypass every attachment gate. Centralized here so we
     * don't duplicate the check in every method.
     */
    public function before(User $user, string $ability): ?bool {
        return $user->isSuperAdmin() ? true : null;
    }

    /**
     * Anyone who can see the project can view its attachments.
     */
    public function view(User $user, Attachment $attachment): bool {
        return $this->isProjectMember($user, $attachment->project);
    }

    /**
     * Listing all attachments in a project (Files tab). Uses the
     * `authorize('viewAny', [Attachment::class, $project])` form in the
     * controller.
     */
    public function viewAny(User $user, Project $project): bool {
        return $this->isProjectMember($user, $project);
    }

    /**
     * Any project member can upload a new attachment in that project.
     */
    public function createIn(User $user, Project $project): bool {
        return $this->isProjectMember($user, $project);
    }

    /**
     * Delete allowed for the uploader or the project creator.
     * Super admin already short-circuits in before().
     */
    public function delete(User $user, Attachment $attachment): bool {
        if ((int) $attachment->uploaded_by === (int) $user->id) return true;
        if ((int) $attachment->project?->created_by === (int) $user->id) return true;
        return false;
    }

    /**
     * Any project member can mint public share links for an attachment
     * they can view.
     */
    public function share(User $user, Attachment $attachment): bool {
        return $this->isProjectMember($user, $attachment->project);
    }

    /*---------------------------------------------------------------------------
    | Internals
    ---------------------------------------------------------------------------*/

    private function isProjectMember(User $user, ?Project $project): bool {
        if (! $project) return false;
        if ((int) $project->created_by === (int) $user->id) return true;
        return $project->members()->where('user_id', $user->id)->exists();
    }
}
