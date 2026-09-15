<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\User;
use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_fill_callback;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // fixed seed to keep data consistent
        mt_srand(99);
        $this->faker->seed(99);

        $users = $manager->getRepository(User::class)->findAll();

        /** @var VideoGame[] $videoGames */
        $videoGames = array_fill_callback(0, 50, fn (int $index): VideoGame => (new VideoGame)
            ->setTitle(sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true))
            ->setRating(($index % 5) + 1)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );

        // TODO : Ajouter les tags aux vidéos

        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();

        $reviews = array_fill_callback(0, 100, fn (int $index) => (new Review)
            ->setVideoGame($videoGames[array_rand($videoGames)])
            ->setUser($users[array_rand($users)])
            ->setRating(($index % 5) + 1)
            ->setComment($this->faker->realText(300, 3))
        );

        array_walk($reviews, [$manager, 'persist']);

        $manager->flush();

    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
