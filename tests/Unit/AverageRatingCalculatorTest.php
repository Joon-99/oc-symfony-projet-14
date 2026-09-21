<?php

declare(strict_types=1);

use App\Rating\CalculateAverageRating;
use App\Rating\RatingHandler;
use App\Tests\Support\VideoGameFactory;
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
     *
     * @param array<int> $ratings
     */
    public function testCalculateAverage(array $ratings, ?int $expectedAverage): void
    {
        $videoGame = VideoGameFactory::createWithRatings($ratings);

        $this->averageCalculator->calculateAverage($videoGame);

        $this->assertSame($expectedAverage, $videoGame->getAverageRating());
    }

    /**
     * @return array<string, array{0: array<int>, 1: ?int}>
     */
    public function ratingProvider(): array
    {
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
