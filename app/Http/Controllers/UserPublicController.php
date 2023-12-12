<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Schemas\AuthorSchema;

class UserPublicController extends Controller
{
    public function show(User $user)
    {
        if (! $user->isPublic) {
            abort(404);
        }

        $user->load(['dreamDestinationsList', 'favoriteHostelsList']);

        $articles = $user->articles()->published()->get();
        $articlesCount = $articles->count();
        $schema = AuthorSchema::for($user)->getSchema();

        return view(
            'user.public.show',
            compact('user', 'articles', 'articlesCount', 'schema')
        );
    }
}
