<?php

namespace App\Tests\Functional\Review;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

class NewReviewTest extends FunctionalTestCase
{
    private User $user;
    private string $defaultVideoGameName;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = $this->login();
        $this->defaultVideoGameName = 'jeu-video-1';
    }

    /**
     * @dataProvider validReviewProvider
     */
    public function testValidReviewIsAccepted(int $rating, ?string $comment): void
    {
        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => $this->defaultVideoGameName]);

        $this->submitReview($rating, $comment);

        // Verifies redirection
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        // Verifies that the review is in DB
        $reviewMade = $this->getEntityManager()->getRepository(Review::class)->findByUserAndVideoGame($this->user, $videoGame);
        $this->assertNotNull($reviewMade);
        $this->assertSame($rating, $reviewMade->getRating());
        $this->assertSame($comment, $reviewMade->getComment());

        // Follows the redirection and verifies the review form is no longer present
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('form[name="review"]');
    }

    /**
     * @return array<string, array{rating: int, comment: string|null}>
     */
    public function validReviewProvider(): array
    {
        return [
            'null comment' => ['rating' => 1, 'comment' => null],
            'rating and comment' => ['rating' => 5, 'comment' => 'Great game!'],
        ];
    }

    /**
     * @dataProvider reviewInvalidRatingProvider
     */
    public function testInvalidRatingIsRejected(int|string|null $rating, ?string $comment): void
    {
        $crawler = $this->get("/{$this->defaultVideoGameName}");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#tab-reviews', 'Avis');

        $form = $crawler->selectButton('Poster')->form();
        $csrfToken = $form->get('review[_token]')->getValue();
        $this->client->request($form->getMethod(), $form->getUri(), [
            'review' => ['rating' => $rating, 'comment' => $comment, '_token' => $csrfToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.is-invalid');
    }

    /**
     * @return array<string, array{rating: int|string|null, comment: string}>
     */
    public function reviewInvalidRatingProvider(): array
    {
        return [
            'out of bounds rating (low)' => ['rating' => 0, 'comment' => ''],
            'out of bounds rating (high)' => ['rating' => 6, 'comment' => ''],
            'non-numeric rating' => ['rating' => 'abc', 'comment' => ''],
            'null rating' => ['rating' => null, 'comment' => ''],
        ];
    }

    public function testCommentAtMaxLengthIsAccepted(): void
    {
        $comment = str_repeat('a', Review::COMMENT_MAX_LENGTH);

        $this->submitReview(5, $comment);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => $this->defaultVideoGameName]);

        $reviewMade = $this->getEntityManager()->getRepository(Review::class)->findByUserAndVideoGame($this->user, $videoGame);
        $this->assertNotNull($reviewMade);
        $this->assertSame($comment, $reviewMade->getComment());
    }

    public function testCommentOverMaxLengthIsRejected(): void
    {
        $comment = str_repeat('a', Review::COMMENT_MAX_LENGTH + 1);

        $this->submitReview(5, $comment);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.is-invalid');
    }

    public function testDuplicateReviewIsDenied(): void
    {
        // grabbing the token because the form won't appear on second attempt
        $crawler = $this->get("/{$this->defaultVideoGameName}");
        $csrfToken = $crawler->selectButton('Poster')->form()->get('review[_token]')->getValue();

        $this->submitReview(5, 'First review');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->client->request('POST', "/{$this->defaultVideoGameName}", [
            'review' => ['rating' => 4, 'comment' => 'Second attempt', '_token' => $csrfToken],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAnonymousUserCannotSubmitFormReview(): void
    {
        $this->client->request('GET', '/auth/logout');

        $crawler = $this->get("/{$this->defaultVideoGameName}");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('form[name="review"]'); // the form must not be displayed
    }

    public function testAnonymousUserCannotSubmitDirectReview(): void
    {
        $this->client->request('GET', '/auth/logout');

        $this->client->request('POST', "/{$this->defaultVideoGameName}", [
            'review' => ['rating' => 5, 'comment' => 'Anonymous attempt'],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY); // not 401, an anonymous user requesting a submit would fail the csrf check
    }

    private function submitReview(int $rating, ?string $comment): void
    {
        $crawler = $this->get("/{$this->defaultVideoGameName}");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Poster')->form([
            'review[rating]' => $rating,
            'review[comment]' => $comment,
        ]);
        $this->client->submit($form);
    }
}
