<?php

namespace App\Observers;

use App\Models\User;
use App\Services\RemovedUserService;

class UserObserver
{
    public const USER_ID_FOR_REPLACE = 1;

    public function deleting(User $user)
    {
        (new RemovedUserService($user, self::USER_ID_FOR_REPLACE))->moveRelationsToNewUser();
    }

    public function saving(User $user): void
    {
        $user->slug = str($user->nickname)->slug();
    }
}
