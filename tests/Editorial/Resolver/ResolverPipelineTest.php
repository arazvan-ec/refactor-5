<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\EditorialAggregate;
use App\Editorial\Resolver\BodyPhotosResolver;
use App\Editorial\Resolver\CommentsResolver;
use App\Editorial\Resolver\InsertedNewsResolver;
use App\Editorial\Resolver\InsertedNewsResult;
use App\Editorial\Resolver\MembershipResolver;
use App\Editorial\Resolver\MultimediaResolutionResult;
use App\Editorial\Resolver\MultimediaResolver;
use App\Editorial\Resolver\OpeningResolver;
use App\Editorial\Resolver\RecommendedResolver;
use App\Editorial\Resolver\RecommendedResult;
use App\Editorial\Resolver\ResolverPipeline;
use App\Editorial\Resolver\SectionResolver;
use App\Editorial\Resolver\SignaturesResolver;
use App\Editorial\Resolver\TagsResolver;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResolverPipeline::class)]
final class ResolverPipelineTest extends TestCase
{
    private SectionResolver&MockObject $sectionResolver;
    private TagsResolver&MockObject $tagsResolver;
    private SignaturesResolver&MockObject $signaturesResolver;
    private MultimediaResolver&MockObject $multimediaResolver;
    private OpeningResolver&MockObject $openingResolver;
    private BodyPhotosResolver&MockObject $bodyPhotosResolver;
    private InsertedNewsResolver&MockObject $insertedNewsResolver;
    private RecommendedResolver&MockObject $recommendedResolver;
    private MembershipResolver&MockObject $membershipResolver;
    private CommentsResolver&MockObject $commentsResolver;
    private ResolverPipeline $pipeline;

    protected function setUp(): void
    {
        $this->sectionResolver = $this->createMock(SectionResolver::class);
        $this->tagsResolver = $this->createMock(TagsResolver::class);
        $this->signaturesResolver = $this->createMock(SignaturesResolver::class);
        $this->multimediaResolver = $this->createMock(MultimediaResolver::class);
        $this->openingResolver = $this->createMock(OpeningResolver::class);
        $this->bodyPhotosResolver = $this->createMock(BodyPhotosResolver::class);
        $this->insertedNewsResolver = $this->createMock(InsertedNewsResolver::class);
        $this->recommendedResolver = $this->createMock(RecommendedResolver::class);
        $this->membershipResolver = $this->createMock(MembershipResolver::class);
        $this->commentsResolver = $this->createMock(CommentsResolver::class);

        $this->pipeline = new ResolverPipeline(
            $this->sectionResolver,
            $this->tagsResolver,
            $this->signaturesResolver,
            $this->multimediaResolver,
            $this->openingResolver,
            $this->bodyPhotosResolver,
            $this->insertedNewsResolver,
            $this->recommendedResolver,
            $this->membershipResolver,
            $this->commentsResolver,
        );
    }

    #[Test]
    public function resolveShouldReturnEditorialAggregateWithAllData(): void
    {
        $editorialId = 'editorial-123';
        $siteId = '1';

        // Setup editorial mock
        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $multimediaMock = $this->createMock(MultimediaEditorial::class);

        $bodyMock = $this->createMock(Body::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->method('body')->willReturn($bodyMock);

        // Setup section
        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn($siteId);
        $this->sectionResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($sectionMock);

        // Setup membership (start early)
        $membershipPromise = $this->createMock(Promise::class);
        $membershipLinks = ['link1', 'link2'];
        $this->membershipResolver
            ->expects(static::once())
            ->method('startPromise')
            ->with($editorialMock, $siteId)
            ->willReturn(['promise' => $membershipPromise, 'links' => $membershipLinks]);

        // Setup tags
        $tagMock = $this->createMock(Tag::class);
        $this->tagsResolver
            ->expects(static::once())
            ->method('resolve')
            ->willReturn([$tagMock]);

        // Setup signatures
        $signatureData = ['journalistId' => '1', 'name' => 'Test'];
        $this->signaturesResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock, $sectionMock)
            ->willReturn([$signatureData]);

        // Setup multimedia async via startAsyncForEditorial
        $mainPromise = $this->createMock(Promise::class);
        $mainMultimediaResult = new MultimediaResolutionResult(promises: [$mainPromise]);
        $this->multimediaResolver
            ->expects(static::once())
            ->method('startAsyncForEditorial')
            ->with($editorialMock)
            ->willReturn($mainMultimediaResult);

        // Setup opening
        $openingData = ['opening-key' => ['opening' => 'data']];
        $this->openingResolver
            ->expects(static::once())
            ->method('resolve')
            ->willReturn($openingData);

        // Setup body photos
        $photoMock = $this->createMock(Photo::class);
        $this->bodyPhotosResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($bodyMock)
            ->willReturn(['photo-1' => $photoMock]);

        // Setup inserted news (typed DTO)
        $insertedPromise = $this->createMock(Promise::class);
        $this->insertedNewsResolver
            ->expects(static::once())
            ->method('resolve')
            ->willReturn(new InsertedNewsResult(
                groups: ['inserted-1' => ['editorial' => $editorialMock, 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => 'mm-1']],
                multimediaResult: new MultimediaResolutionResult(promises: [$insertedPromise]),
            ));

        // Setup recommended (typed DTO)
        $recommendedPromise = $this->createMock(Promise::class);
        $recommendedEditorial = $this->createMock(Editorial::class);
        $this->recommendedResolver
            ->expects(static::once())
            ->method('resolve')
            ->willReturn(new RecommendedResult(
                groups: ['rec-1' => ['editorial' => $recommendedEditorial, 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => 'mm-2']],
                news: [$recommendedEditorial],
                multimediaResult: new MultimediaResolutionResult(promises: [$recommendedPromise]),
            ));

        // Setup comments
        $this->commentsResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialId)
            ->willReturn(42);

        // Settle all promises in batch (main + inserted + recommended)
        $resolvedMultimedia = $this->createMock(AbstractMultimedia::class);
        $this->multimediaResolver
            ->expects(static::once())
            ->method('settle')
            ->with([$mainPromise, $insertedPromise, $recommendedPromise])
            ->willReturn(['mm-resolved' => $resolvedMultimedia]);

        // Resolve membership
        $resolvedMembershipLinks = ['link1' => 'resolved1', 'link2' => 'resolved2'];
        $this->membershipResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($membershipPromise, $membershipLinks)
            ->willReturn($resolvedMembershipLinks);

        // Execute
        $aggregate = $this->pipeline->resolve($editorialMock);

        // Assert
        static::assertInstanceOf(EditorialAggregate::class, $aggregate);
        static::assertSame($editorialMock, $aggregate->editorial);
        static::assertSame($sectionMock, $aggregate->section);
        static::assertCount(1, $aggregate->tags);
        static::assertCount(1, $aggregate->signatures);
        static::assertCount(1, $aggregate->multimedia);
        static::assertSame($openingData, $aggregate->multimediaOpening);
        static::assertCount(1, $aggregate->bodyPhotos);
        static::assertCount(1, $aggregate->insertedNews);
        static::assertCount(1, $aggregate->recommendedEditorials);
        static::assertCount(1, $aggregate->recommendedNews);
        static::assertSame($resolvedMembershipLinks, $aggregate->membershipLinks);
        static::assertSame(42, $aggregate->commentCount);
    }

    #[Test]
    public function resolveShouldSkipMultimediaAsyncForWidgets(): void
    {
        $editorialId = 'editorial-widget';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $bodyMock = $this->createMock(Body::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->method('body')->willReturn($bodyMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('1');

        $this->sectionResolver->method('resolve')->willReturn($sectionMock);

        $membershipPromise = $this->createMock(Promise::class);
        $this->membershipResolver->method('startPromise')->willReturn(['promise' => $membershipPromise, 'links' => []]);
        $this->membershipResolver->method('resolve')->willReturn([]);

        $this->tagsResolver->method('resolve')->willReturn([]);
        $this->signaturesResolver->method('resolve')->willReturn([]);
        $this->openingResolver->method('resolve')->willReturn([]);
        $this->bodyPhotosResolver->method('resolve')->willReturn([]);
        $this->commentsResolver->method('resolve')->willReturn(0);

        // startAsyncForEditorial returns empty result (Widget check is inside MultimediaResolver)
        $this->multimediaResolver
            ->method('startAsyncForEditorial')
            ->willReturn(new MultimediaResolutionResult());

        $this->insertedNewsResolver->method('resolve')->willReturn(new InsertedNewsResult(
            groups: [],
            multimediaResult: new MultimediaResolutionResult(),
        ));
        $this->recommendedResolver->method('resolve')->willReturn(new RecommendedResult(
            groups: [],
            news: [],
            multimediaResult: new MultimediaResolutionResult(),
        ));

        // Settle is called with empty array
        $this->multimediaResolver
            ->expects(static::once())
            ->method('settle')
            ->with([])
            ->willReturn([]);

        $aggregate = $this->pipeline->resolve($editorialMock);

        static::assertSame([], $aggregate->multimedia);
    }

    #[Test]
    public function resolveShouldHandleNullMultimediaId(): void
    {
        $editorialId = 'editorial-no-mm';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $bodyMock = $this->createMock(Body::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->method('body')->willReturn($bodyMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('1');

        $this->sectionResolver->method('resolve')->willReturn($sectionMock);

        $membershipPromise = $this->createMock(Promise::class);
        $this->membershipResolver->method('startPromise')->willReturn(['promise' => $membershipPromise, 'links' => []]);
        $this->membershipResolver->method('resolve')->willReturn([]);

        $this->tagsResolver->method('resolve')->willReturn([]);
        $this->signaturesResolver->method('resolve')->willReturn([]);
        $this->openingResolver->method('resolve')->willReturn([]);
        $this->bodyPhotosResolver->method('resolve')->willReturn([]);
        $this->commentsResolver->method('resolve')->willReturn(0);

        // startAsyncForEditorial returns empty result (no multimedia)
        $this->multimediaResolver
            ->method('startAsyncForEditorial')
            ->willReturn(new MultimediaResolutionResult());

        $this->insertedNewsResolver->method('resolve')->willReturn(new InsertedNewsResult(
            groups: [],
            multimediaResult: new MultimediaResolutionResult(),
        ));
        $this->recommendedResolver->method('resolve')->willReturn(new RecommendedResult(
            groups: [],
            news: [],
            multimediaResult: new MultimediaResolutionResult(),
        ));

        $this->multimediaResolver
            ->expects(static::once())
            ->method('settle')
            ->with([])
            ->willReturn([]);

        $aggregate = $this->pipeline->resolve($editorialMock);

        static::assertSame([], $aggregate->multimedia);
    }

    #[Test]
    public function resolveShouldMergeMultimediaOpeningFromAllSources(): void
    {
        $editorialId = 'editorial-merge-opening';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $bodyMock = $this->createMock(Body::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->method('body')->willReturn($bodyMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('1');

        $this->sectionResolver->method('resolve')->willReturn($sectionMock);

        $membershipPromise = $this->createMock(Promise::class);
        $this->membershipResolver->method('startPromise')->willReturn(['promise' => $membershipPromise, 'links' => []]);
        $this->membershipResolver->method('resolve')->willReturn([]);

        $this->tagsResolver->method('resolve')->willReturn([]);
        $this->signaturesResolver->method('resolve')->willReturn([]);
        $this->bodyPhotosResolver->method('resolve')->willReturn([]);
        $this->commentsResolver->method('resolve')->willReturn(0);

        // startAsyncForEditorial returns empty result
        $this->multimediaResolver->method('startAsyncForEditorial')->willReturn(new MultimediaResolutionResult());
        $this->multimediaResolver->method('settle')->willReturn([]);

        // Opening from main editorial
        $this->openingResolver->method('resolve')->willReturn(['main-opening' => ['data' => 'main']]);

        // Opening from inserted news (via MultimediaResolutionResult)
        $this->insertedNewsResolver->method('resolve')->willReturn(new InsertedNewsResult(
            groups: [],
            multimediaResult: new MultimediaResolutionResult(
                multimediaOpening: ['inserted-opening' => ['data' => 'inserted']],
            ),
        ));

        // Opening from recommended (via MultimediaResolutionResult)
        $this->recommendedResolver->method('resolve')->willReturn(new RecommendedResult(
            groups: [],
            news: [],
            multimediaResult: new MultimediaResolutionResult(
                multimediaOpening: ['recommended-opening' => ['data' => 'recommended']],
            ),
        ));

        $aggregate = $this->pipeline->resolve($editorialMock);

        static::assertCount(3, $aggregate->multimediaOpening);
        static::assertArrayHasKey('main-opening', $aggregate->multimediaOpening);
        static::assertArrayHasKey('inserted-opening', $aggregate->multimediaOpening);
        static::assertArrayHasKey('recommended-opening', $aggregate->multimediaOpening);
    }
}
