<?php

namespace App\Services;

use App\Models\User;

class RemovedUserService
{
    public function __construct(
        private User $user, private int $userId
    ) {
    }

    public function moveRelationsToNewUser(): void
    {
        $this
            ->updateReviewsRelation()
            ->updateRatingsRelation()
            ->updateArticlesRelation()
            ->updateAttachedTextsRelation()
            ->updateCityCommentsRelation();
    }

    private function updateReviewsRelation(): static
    {
        $reviews = $this->user->reviews();

        if ($reviews->count()) {
            $reviews->update(['reviewerID' => $this->userId]);
        }

        return $this;
    }

    private function updateRatingsRelation(): static
    {
        $ratings = $this->user->ratings();

        if ($ratings->count()) {
            $ratings->update(['userID' => $this->userId]);
        }

        return $this;
    }

    private function updateArticlesRelation(): static
    {
        $articles = $this->user->articles();

        if ($articles->count()) {
            $articles->update(['userID' => $this->userId]);
        }

        return $this;
    }

    private function updateAttachedTextsRelation(): static
    {
        $attachedText = $this->user->attachedTexts();

        if ($attachedText->count()) {
            $attachedText->update(['userID' => $this->userId]);
        }

        return $this;
    }

    private function updateCityCommentsRelation(): static
    {
        $comments = $this->user->cityComments();

        if ($comments->count()) {
            $comments->update(['userID' => $this->userId]);
        }

        return $this;
    }
}
