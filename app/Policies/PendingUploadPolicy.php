<?php

namespace App\Policies;

use App\Models\PendingUpload;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PendingUploadPolicy
{
    public function update(User $user, PendingUpload $pendingUpload): Response
    {
        return $pendingUpload->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
