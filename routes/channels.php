<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('import-started.{system}', function (User $user) {
    return $user->isAdmin();
});

Broadcast::channel('import-page-added.{system}', function (User $user) {
    return $user->isAdmin();
});

Broadcast::channel('import-inserted.{system}', function (User $user) {
    return $user->isAdmin();
});

Broadcast::channel('import-finished.{system}', function (User $user) {
    return $user->isAdmin();
});

Broadcast::channel('import-update', function (User $user) {
    return $user->isAdmin();
});
