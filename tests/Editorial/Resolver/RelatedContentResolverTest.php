<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\InsertedNewsResolver;
use App\Editorial\Resolver\InsertedNewsResult;
use App\Editorial\Resolver\MultimediaResolutionResult;
use App\Editorial\Resolver\RecommendedResolver;
use App\Editorial\Resolver\RecommendedResult;
use App\Editorial\Resolver\RelatedContentResolver;
use App\Editorial\Resolver\RelatedContentResult;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelatedContentResolver::class)]
final class RelatedContentResolverTest extends TestCase
{
    private InsertedNewsResolver&MockObject $insertedNewsResolver;
    private RecommendedResolver&MockObject $recommendedResolver;
    private RelatedContentResolver $resolver;

    protected function setUp(): void
    {
        $this->insertedNewsResolver = $this->createMock(InsertedNewsResolver::class);
        $this->recommendedResolver = $this->createMock(RecommendedResolver::class);

        $this->resolver = new RelatedContentResolver(
            $this->insertedNewsResolver,
            $this->recommendedResolver,
        );
    }

    #[Test]
    public function resolveShouldReturnRelatedContentResult(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);

        $insertedResult = new InsertedNewsResult(
            groups: ['ins-1' => ['editorial' => $editorialMock, 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => 'mm-1']],
            multimediaResult: new MultimediaResolutionResult(),
        );

        $recommendedEditorial = $this->createMock(Editorial::class);
        $recommendedResult = new RecommendedResult(
            groups: ['rec-1' => ['editorial' => $recommendedEditorial, 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => 'mm-2']],
            news: [$recommendedEditorial],
            multimediaResult: new MultimediaResolutionResult(),
        );

        $this->insertedNewsResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($insertedResult);

        $this->recommendedResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($recommendedResult);

        $result = $this->resolver->resolve($editorialMock);

        static::assertInstanceOf(RelatedContentResult::class, $result);
        static::assertSame($insertedResult, $result->insertedNews);
        static::assertSame($recommendedResult, $result->recommended);
    }

    #[Test]
    public function resolveShouldHandleEmptyResults(): void
    {
        $editorialMock = $this->createMock(Editorial::class);

        $insertedResult = new InsertedNewsResult(
            groups: [],
            multimediaResult: new MultimediaResolutionResult(),
        );

        $recommendedResult = new RecommendedResult(
            groups: [],
            news: [],
            multimediaResult: new MultimediaResolutionResult(),
        );

        $this->insertedNewsResolver->method('resolve')->willReturn($insertedResult);
        $this->recommendedResolver->method('resolve')->willReturn($recommendedResult);

        $result = $this->resolver->resolve($editorialMock);

        static::assertSame([], $result->insertedNews->groups);
        static::assertSame([], $result->recommended->groups);
        static::assertSame([], $result->recommended->news);
    }
}
