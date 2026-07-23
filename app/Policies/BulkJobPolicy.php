<?php

namespace App\Policies;

use App\Models\BulkJob;
use App\Models\User;

class BulkJobPolicy
{
    public function view(User $user, BulkJob $bulkJob): bool   { return $user->id === $bulkJob->user_id; }
    public function delete(User $user, BulkJob $bulkJob): bool { return $user->id === $bulkJob->user_id; }
}
