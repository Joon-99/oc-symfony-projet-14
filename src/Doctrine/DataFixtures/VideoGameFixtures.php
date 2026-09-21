<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $tagValues = ['Action', 'Adventure', 'RPG', 'Strategy',
            'Simulation', 'Sports', 'Puzzle', 'Horror',
            'Shooter', 'Platformer', 'Fighting', 'MMO',
            'Music', 'TCG', 'Sandbox', 'Stealth'];

        // fixed seed to keep data consistent
        mt_srand(99);
        $this->faker->seed(99);

        $users = $manager->getRepository(User::class)->findAll();

        /** @var VideoGame[] $videoGames */
        $videoGames = \array_fill_callback(0, 50, fn (int $index): VideoGame => (new VideoGame())
            ->setTitle(sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new \DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true))
            ->setRating(($index % 5) + 1)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );
        array_walk($videoGames, [$manager, 'persist']);

        $tags = \array_fill_callback(0, count($tagValues), fn (int $index): Tag => (new Tag())
            ->setName($tagValues[$index])
        );
        array_walk($tags, [$manager, 'persist']);

        $reviews = \array_fill_callback(0, 100, function (int $index) use ($videoGames, $users): Review {
            $review = (new Review())
                ->setUser($users[array_rand($users)])
                ->setRating(($index % 5) + 1)
                ->setComment($this->faker->realText(300, 3));

            /** @var VideoGame $randomGame */
            $randomGame = $videoGames[array_rand($videoGames)];
            $randomGame->addReview($review);

            return $review;
        });

        array_walk($reviews, [$manager, 'persist']);

        foreach ($videoGames as $videoGame) {
            $maxTags = $this->faker->numberBetween(1, 4);
            for ($i = 0; $i < $maxTags; ++$i) {
                $videoGame->addTag($this->faker->unique()->randomElement($tags));
            }
            $this->faker->unique(true); // resets unique to keep all possible tags next game
            $this->calculateAverageRating->calculateAverage($videoGame);
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
