<?php

namespace App\Policies;

use App\Models\PbcDocument;
use App\Models\User;

class PbcDocumentPolicy
{
    public function upload_document(User $user): bool
    {
        return in_array($user->role, ['system_admin', 'engagement_partner', 'manager', 'associate', 'guest']);
    }

    public function download_document(User $user): bool
    {
        return in_array($user->role, ['system_admin', 'engagement_partner', 'manager', 'associate', 'guest']);
    }

    public function approve_document(User $user): bool
    {
        return in_array($user->role, ['system_admin', 'engagement_partner', 'manager', 'associate']);
    }

    public function reject_document(User $user): bool
    {
        return in_array($user->role, ['system_admin', 'engagement_partner', 'manager', 'associate']);
    }

    public function delete_document(User $user): bool
    {
        return in_array($user->role, ['system_admin', 'engagement_partner', 'manager', 'associate']);
    }
}
