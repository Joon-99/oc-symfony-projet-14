<?php

declare(strict_types=1);

use App\Model\Entity\Review;
use App\Rating\CountRatingsPerValue;
use App\Rating\RatingHandler;
use App\Tests\Support\VideoGameFactory;
use PHPUnit\Framework\TestCase;

class CountRatingsPerValueTest extends TestCase
{
    private CountRatingsPerValue $noteCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->noteCalculator = new RatingHandler();
    }


    /**
     * @dataProvider ratingProvider
     */
    public function testCountRatingsPerValue(array $ratings, array $expectedCounts): void
    {
        $videoGame = VideoGameFactory::createWithRatings($ratings);

        $this->noteCalculator->countRatingsPerValue($videoGame);

        $this->assertSame($expectedCounts, $videoGame->getNumberOfRatingsPerValue()->toArray());
    }

    /**
     * Tests that the count is cleared before calculation
     */
    public function testCountRatingsPerValueResetsPreviousCounts(): void
    {
        $videoGame = VideoGameFactory::createWithRatings([5, 5, 5]);

        $this->noteCalculator->countRatingsPerValue($videoGame);
        $this->assertSame(
            [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 3,
            ],
            $videoGame->getNumberOfRatingsPerValue()->toArray()
        );

        $videoGame->getReviews()->clear();

        foreach ([1, 2] as $rating) {
            $review = new Review();
            $review->setRating($rating);
            $videoGame->addReview($review);
        }

        $this->noteCalculator->countRatingsPerValue($videoGame);

        $this->assertSame(
            [
                1 => 1,
                2 => 1,
                3 => 0,
                4 => 0,
                5 => 0,
            ],
            $videoGame->getNumberOfRatingsPerValue()->toArray()
        );
    }

    public function ratingProvider(): array {
        return [
            'all ratings' => [
                [1, 2, 3, 4, 5],
                [
                    1 => 1,
                    2 => 1,
                    3 => 1,
                    4 => 1,
                    5 => 1,
                ],
            ],
            'no ratings' => [
                [],
                [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                ],
            ],
            'one rating' => [
                [3],
                [
                    1 => 0,
                    2 => 0,
                    3 => 1,
                    4 => 0,
                    5 => 0,
                ],
            ],
            'many ratings' => [
                [1, 2, 3, 4, 5, 1, 2, 3, 4, 5, 2, 5, 5],
                [
                    1 => 2,
                    2 => 3,
                    3 => 2,
                    4 => 2,
                    5 => 4,
                ],
            ],
        ];
    }
}