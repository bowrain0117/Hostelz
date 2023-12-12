<?php

namespace App\Services;

use App\Models\UsersSearchHistory;

class UserAdminService
{
    public function storeUserSearch($fields): void
    {
        $userSearchHistory = auth()->user()->searchHistory();

        $userSearchHistory->create($fields);

        if ($userSearchHistory->count() > UsersSearchHistory::MAX_SEARCHES) {
            $ids = $userSearchHistory->select('id')->latest()->get()->slice(UsersSearchHistory::MAX_SEARCHES);

            UsersSearchHistory::destroy($ids->toArray());
        }
    }
}
