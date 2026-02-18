<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\CommentsResolver;
use App\Editorial\Resolver\EditorialMetadata;
use App\Editorial\Resolver\EditorialMetadataResolver;
use App\Editorial\Resolver\SectionResolver;
use App\Editorial\Resolver\SignaturesResolver;
use App\Editorial\Resolver\TagsResolver;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(EditorialMetadataResolver::class)]
final class EditorialMetadataResolverTest extends TestCase
{
    private SectionResolver&MockObject $sectionResolver;
    private TagsResolver&MockObject $tagsResolver;
    private SignaturesResolver&MockObject $signaturesResolver;
    private CommentsResolver&MockObject $commentsResolver;
    private EditorialMetadataResolver $resolver;

    protected function setUp(): void
    {
        $this->sectionResolver = $this->createMock(SectionResolver::class);
        $this->tagsResolver = $this->createMock(TagsResolver::class);
        $this->signaturesResolver = $this->createMock(SignaturesResolver::class);
        $this->commentsResolver = $this->createMock(CommentsResolver::class);

        $this->resolver = new EditorialMetadataResolver(
            $this->sectionResolver,
            $this->tagsResolver,
            $this->signaturesResolver,
            $this->commentsResolver,
        );
    }

    #[Test]
    public function resolveShouldReturnEditorialMetadataWithAllFields(): void
    {
        $editorialId = 'editorial-123';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);

        $sectionMock = $this->createMock(Section::class);
        $this->sectionResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($sectionMock);

        $tagMock = $this->createMock(Tag::class);
        $this->tagsResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn([$tagMock]);

        $signatureData = ['journalistId' => '1', 'name' => 'Test'];
        $this->signaturesResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock, $sectionMock)
            ->willReturn([$signatureData]);

        $this->commentsResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialId)
            ->willReturn(42);

        $result = $this->resolver->resolve($editorialMock);

        static::assertInstanceOf(EditorialMetadata::class, $result);
        static::assertSame($sectionMock, $result->section);
        static::assertSame([$tagMock], $result->tags);
        static::assertSame([$signatureData], $result->signatures);
        static::assertSame(42, $result->commentCount);
    }

    #[Test]
    public function resolveShouldPassSectionToSignaturesResolver(): void
    {
        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn('ed-1');

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);

        $sectionMock = $this->createMock(Section::class);
        $this->sectionResolver->method('resolve')->willReturn($sectionMock);
        $this->tagsResolver->method('resolve')->willReturn([]);
        $this->commentsResolver->method('resolve')->willReturn(0);

        $this->signaturesResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock, $sectionMock)
            ->willReturn([]);

        $this->resolver->resolve($editorialMock);
    }
}
