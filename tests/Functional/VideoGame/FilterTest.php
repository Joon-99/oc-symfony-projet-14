<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Tag;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;

final class FilterTest extends FunctionalTestCase
{
    /**
     * Tests the pagination functionality.
     */
    public function testPagination(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();

        self::assertSelectorCount(10, 'article.game-card');
        $firstVGText = $this->client->getCrawler()->filter('article.game-card')->first()->text();
        $paginationLink = $this->client->getCrawler()->filter('nav[aria-label="Pagination"]')->selectLink('2')->link();
        $this->client->click($paginationLink);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $secondVGText = $this->client->getCrawler()->filter('article.game-card')->first()->text();
        self::assertNotEquals($firstVGText, $secondVGText);
    }

    /**
     * Tests the search by text functionality.
     */
    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    /**
     * Tests the filtering of video games by tags.
     *
     * @dataProvider tagProvider
     *
     * @param array<string> $filterTags
     */
    public function testShouldFilterVideogamesByTag(array $filterTags): void
    {
        $filterTagIds = array_map(
            fn (string $name) => (string) $this->getEntityManager()->getRepository(Tag::class)->findOneBy(['name' => $name])->getId(),
            $filterTags
        );

        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        // request a page large enough to fit every possible match, so pagination can't truncate the result set
        $this->client->request('GET', '/', ['filter' => ['tags' => $filterTagIds], 'limit' => '50']);
        self::assertResponseIsSuccessful();

        // rebuilds all the VG titles to determine the expected result
        $allVideoGames = $this->getEntityManager()->getRepository(VideoGame::class)->findAll();
        $expectedTitles = [];
        foreach ($allVideoGames as $videoGame) {
            $videoGameTagIds = array_map(fn (Tag $tag) => (string) $tag->getId(), $videoGame->getTags()->toArray());
            if ([] === array_diff($filterTagIds, $videoGameTagIds)) {
                $expectedTitles[] = $videoGame->getTitle();
            }
        }
        sort($expectedTitles);

        $actualTitles = $this->extractVideoGameTitles($this->client->getCrawler());
        sort($actualTitles);

        self::assertSame($expectedTitles, $actualTitles);
    }

    /**
     * @return array<string, array{0: array<string>}>
     */
    public function tagProvider(): array
    {
        return [
            'tag with more than a page of matches' => [['Strategy']],
            'one tag' => [['Action']],
            'two tags' => [['Action', 'Adventure']],
            'three tags' => [['Action', 'Adventure', 'RPG']],
        ];
    }

    /**
     * Tests that an empty tag filter shows unfiltered results.
     */
    public function testEmptyTagFilterShowsUnfilteredResults(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        $initialVideoGameTitles = $this->extractVideoGameTitles($this->client->getCrawler());

        $this->client->submitForm('Filtrer', ['filter[tags]' => []], 'GET');
        self::assertResponseIsSuccessful();
        $consequentVideoGameTitles = $this->extractVideoGameTitles($this->client->getCrawler());

        self::assertSame($initialVideoGameTitles, $consequentVideoGameTitles);
    }

    /**
     * Extracts the titles of video games from a given crawler.
     *
     * @return string[]
     */
    private function extractVideoGameTitles(Crawler $crawler): array
    {
        return $crawler->filter('article.game-card')->each(
            fn (Crawler $videoGame) => trim($videoGame->filter('.game-card-title')->text())
        );
    }
}
