<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;

final class VideoGameFactory
{
    public static function createWithRatings(array $ratings): VideoGame
    {
        $videoGame = new VideoGame();

        foreach ($ratings as $rating) {
            $review = new Review();
            $review->setRating($rating);
            $videoGame->addReview($review);
        }

        return $videoGame;
    }
}
