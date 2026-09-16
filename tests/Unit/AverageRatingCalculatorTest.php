<?php

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

class AverageRatingCalculatorTest extends TestCase
{
    private CalculateAverageRating $averageCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->averageCalculator = new RatingHandler();
    }

    /** 
     * @dataProvider ratingProvider
     */
    public function testCalculateAverage(array $ratings, ?int $expectedAverage): void
    {
        $videoGame = new VideoGame();

        foreach ($ratings as $rating) {
            $review = new Review();
            $review->setRating($rating);
            $videoGame->addReview($review);
        }

        $this->averageCalculator->calculateAverage($videoGame);

        $this->assertSame($expectedAverage, $videoGame->getAverageRating());

    }

    // Providers
    public function ratingProvider(): array {

        return [
            'no reviews' => [[], null],
            'one review' => [[4], 4],
            'all ratings' => [[1, 2, 3, 4, 5], 3],
            'rounding up' => [[1, 1, 1, 1, 2], 2],
            'minimum rating' => [[1], 1],
            'maximum rating' => [[5], 5],
        ];


    }
}