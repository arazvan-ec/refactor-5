<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\InsertedNewsResult;
use App\Editorial\Resolver\MultimediaResolutionResult;
use App\Editorial\Resolver\RecommendedResult;
use App\Editorial\Resolver\RelatedContentResult;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelatedContentResult::class)]
final class RelatedContentResultTest extends TestCase
{
    #[Test]
    public function combinedMultimediaResultShouldMergeBothResults(): void
    {
        $promise1 = $this->createMock(Promise::class);
        $promise2 = $this->createMock(Promise::class);

        $insertedResult = new InsertedNewsResult(
            groups: [],
            multimediaResult: new MultimediaResolutionResult(
                promises: [$promise1],
                multimediaOpening: ['ins-key' => ['data' => 'inserted']],
            ),
        );

        $recommendedResult = new RecommendedResult(
            groups: [],
            news: [],
            multimediaResult: new MultimediaResolutionResult(
                promises: [$promise2],
                multimediaOpening: ['rec-key' => ['data' => 'recommended']],
            ),
        );

        $relatedContent = new RelatedContentResult(
            insertedNews: $insertedResult,
            recommended: $recommendedResult,
        );

        $combined = $relatedContent->combinedMultimediaResult();

        static::assertCount(2, $combined->promises);
        static::assertSame($promise1, $combined->promises[0]);
        static::assertSame($promise2, $combined->promises[1]);
        static::assertCount(2, $combined->multimediaOpening);
        static::assertArrayHasKey('ins-key', $combined->multimediaOpening);
        static::assertArrayHasKey('rec-key', $combined->multimediaOpening);
    }

    #[Test]
    public function combinedMultimediaResultShouldHandleEmptyResults(): void
    {
        $relatedContent = new RelatedContentResult(
            insertedNews: new InsertedNewsResult(
                groups: [],
                multimediaResult: new MultimediaResolutionResult(),
            ),
            recommended: new RecommendedResult(
                groups: [],
                news: [],
                multimediaResult: new MultimediaResolutionResult(),
            ),
        );

        $combined = $relatedContent->combinedMultimediaResult();

        static::assertSame([], $combined->promises);
        static::assertSame([], $combined->multimediaOpening);
    }
}
