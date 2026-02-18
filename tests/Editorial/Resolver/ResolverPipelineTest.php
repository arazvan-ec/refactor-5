<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\EditorialAggregate;
use App\Editorial\Resolver\ContentMediaResolver;
use App\Editorial\Resolver\ContentMediaResult;
use App\Editorial\Resolver\EditorialMetadata;
use App\Editorial\Resolver\EditorialMetadataResolver;
use App\Editorial\Resolver\InsertedNewsResult;
use App\Editorial\Resolver\MembershipPromiseResult;
use App\Editorial\Resolver\MembershipResolver;
use App\Editorial\Resolver\MultimediaResolutionResult;
use App\Editorial\Resolver\RecommendedResult;
use App\Editorial\Resolver\RelatedContentResolver;
use App\Editorial\Resolver\RelatedContentResult;
use App\Editorial\Resolver\ResolverPipeline;
use Ec\Editorial\Domain\Model\Editorial;
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
    private EditorialMetadataResolver&MockObject $metadataResolver;
    private ContentMediaResolver&MockObject $contentMediaResolver;
    private RelatedContentResolver&MockObject $relatedContentResolver;
    private MembershipResolver&MockObject $membershipResolver;
    private ResolverPipeline $pipeline;

    protected function setUp(): void
    {
        $this->metadataResolver = $this->createMock(EditorialMetadataResolver::class);
        $this->contentMediaResolver = $this->createMock(ContentMediaResolver::class);
        $this->relatedContentResolver = $this->createMock(RelatedContentResolver::class);
        $this->membershipResolver = $this->createMock(MembershipResolver::class);

        $this->pipeline = new ResolverPipeline(
            $this->metadataResolver,
            $this->contentMediaResolver,
            $this->relatedContentResolver,
            $this->membershipResolver,
        );
    }

    #[Test]
    public function resolveShouldReturnEditorialAggregateWithAllData(): void
    {
        $siteId = '1';
        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn($siteId);

        $tagMock = $this->createMock(Tag::class);
        $signatureData = ['journalistId' => '1', 'name' => 'Test'];

        $metadata = new EditorialMetadata(
            section: $sectionMock,
            tags: [$tagMock],
            signatures: [$signatureData],
            commentCount: 42,
        );
        $this->metadataResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($metadata);

        $membershipPromise = $this->createMock(Promise::class);
        $this->membershipResolver
            ->expects(static::once())
            ->method('startPromise')
            ->with($editorialMock, $siteId)
            ->willReturn(new MembershipPromiseResult(promise: $membershipPromise, links: ['link1', 'link2']));

        $mainMultimediaResult = new MultimediaResolutionResult(
            promises: [$this->createMock(Promise::class)],
        );
        $openingData = ['opening-key' => ['opening' => 'data']];
        $photoMock = $this->createMock(Photo::class);

        $contentMedia = new ContentMediaResult(
            multimediaResult: $mainMultimediaResult,
            multimediaOpening: $openingData,
            bodyPhotos: ['photo-1' => $photoMock],
        );
        $this->contentMediaResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($contentMedia);

        $insertedResult = new InsertedNewsResult(
            groups: ['ins-1' => ['editorial' => $editorialMock, 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => 'mm-1']],
            multimediaResult: new MultimediaResolutionResult(promises: [$this->createMock(Promise::class)]),
        );
        $recommendedEditorial = $this->createMock(Editorial::class);
        $recommendedResult = new RecommendedResult(
            groups: ['rec-1' => ['editorial' => $recommendedEditorial, 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => 'mm-2']],
            news: [$recommendedEditorial],
            multimediaResult: new MultimediaResolutionResult(promises: [$this->createMock(Promise::class)]),
        );

        $relatedContent = new RelatedContentResult(
            insertedNews: $insertedResult,
            recommended: $recommendedResult,
        );
        $this->relatedContentResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($relatedContent);

        $resolvedMultimedia = $this->createMock(AbstractMultimedia::class);
        $this->contentMediaResolver
            ->expects(static::once())
            ->method('settleAllMultimedia')
            ->willReturn(['mm-resolved' => $resolvedMultimedia]);

        $this->contentMediaResolver
            ->expects(static::once())
            ->method('mergeOpenings')
            ->willReturn($openingData);

        $resolvedMembershipLinks = ['link1' => 'resolved1', 'link2' => 'resolved2'];
        $this->membershipResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($membershipPromise, ['link1', 'link2'])
            ->willReturn($resolvedMembershipLinks);

        $aggregate = $this->pipeline->resolve($editorialMock);

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
    public function resolveShouldHandleEmptyResults(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('1');

        $this->metadataResolver->method('resolve')->willReturn(new EditorialMetadata(
            section: $sectionMock,
            tags: [],
            signatures: [],
            commentCount: 0,
        ));

        $this->membershipResolver->method('startPromise')->willReturn(
            new MembershipPromiseResult(promise: null, links: []),
        );
        $this->membershipResolver->method('resolve')->willReturn([]);

        $this->contentMediaResolver->method('resolve')->willReturn(new ContentMediaResult(
            multimediaResult: new MultimediaResolutionResult(),
            multimediaOpening: [],
            bodyPhotos: [],
        ));

        $this->relatedContentResolver->method('resolve')->willReturn(new RelatedContentResult(
            insertedNews: new InsertedNewsResult(groups: [], multimediaResult: new MultimediaResolutionResult()),
            recommended: new RecommendedResult(groups: [], news: [], multimediaResult: new MultimediaResolutionResult()),
        ));

        $this->contentMediaResolver->method('settleAllMultimedia')->willReturn([]);
        $this->contentMediaResolver->method('mergeOpenings')->willReturn([]);

        $aggregate = $this->pipeline->resolve($editorialMock);

        static::assertSame([], $aggregate->multimedia);
        static::assertSame([], $aggregate->multimediaOpening);
        static::assertSame([], $aggregate->bodyPhotos);
        static::assertSame([], $aggregate->insertedNews);
        static::assertSame([], $aggregate->recommendedEditorials);
        static::assertSame([], $aggregate->recommendedNews);
        static::assertSame([], $aggregate->membershipLinks);
        static::assertSame(0, $aggregate->commentCount);
    }

    #[Test]
    public function resolveShouldPassCorrectArgumentsToMembershipResolver(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('5');

        $this->metadataResolver->method('resolve')->willReturn(new EditorialMetadata(
            section: $sectionMock,
            tags: [],
            signatures: [],
            commentCount: 0,
        ));

        $membershipPromise = $this->createMock(Promise::class);
        $membershipLinks = ['link-a', 'link-b'];
        $this->membershipResolver
            ->expects(static::once())
            ->method('startPromise')
            ->with($editorialMock, '5')
            ->willReturn(new MembershipPromiseResult(promise: $membershipPromise, links: $membershipLinks));

        $this->membershipResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($membershipPromise, $membershipLinks)
            ->willReturn([]);

        $this->contentMediaResolver->method('resolve')->willReturn(new ContentMediaResult(
            multimediaResult: new MultimediaResolutionResult(),
            multimediaOpening: [],
            bodyPhotos: [],
        ));

        $this->relatedContentResolver->method('resolve')->willReturn(new RelatedContentResult(
            insertedNews: new InsertedNewsResult(groups: [], multimediaResult: new MultimediaResolutionResult()),
            recommended: new RecommendedResult(groups: [], news: [], multimediaResult: new MultimediaResolutionResult()),
        ));

        $this->contentMediaResolver->method('settleAllMultimedia')->willReturn([]);
        $this->contentMediaResolver->method('mergeOpenings')->willReturn([]);

        $this->pipeline->resolve($editorialMock);
    }
}
