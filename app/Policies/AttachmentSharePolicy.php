<?php

namespace App\Policies;

use App\Models\AttachmentShare;
use App\Models\User;

class AttachmentSharePolicy {

    public function before(User $user, string $ability): ?bool {
        return $user->isSuperAdmin() ? true : null;
    }

    /**
     * Toggle / edit / revoke a share. Limited to the share creator and
     * the project creator — looser than delete-on-attachment because a
     * share is specifically an outbound link that its creator owns, and
     * the project creator retains override authority to kill any leak.
     */
    public function manage(User $user, AttachmentShare $share): bool {
        if ((int) $share->created_by === (int) $user->id) return true;

        $project = $share->attachment?->project;
        if ($project && (int) $project->created_by === (int) $user->id) return true;

        return false;
    }
}
