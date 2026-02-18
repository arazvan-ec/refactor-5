<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\TagsResolver;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Tag as EditorialTag;
use Ec\Editorial\Domain\Model\Tags;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(TagsResolver::class)]
final class TagsResolverTest extends TestCase
{
    private TagsResolver $resolver;
    private QueryTagClient $queryTagClient;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->queryTagClient = $this->createMock(QueryTagClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver = new TagsResolver($this->queryTagClient, $this->logger);
    }

    #[Test]
    public function resolveShouldReturnTagsForEditorial(): void
    {
        $editorialTag = $this->createMock(EditorialTag::class);
        $editorialTag->method('id')->willReturn('tag-1');

        $tags = new Tags();
        $tags->addItem($editorialTag);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock
            ->expects(static::once())
            ->method('tags')
            ->willReturn($tags);

        $resolvedTag = $this->createMock(Tag::class);
        $this->queryTagClient
            ->expects(static::once())
            ->method('findTagById')
            ->willReturn($resolvedTag);

        $result = $this->resolver->resolve($editorialMock);

        static::assertCount(1, $result);
        static::assertSame($resolvedTag, $result[0]);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenNoTags(): void
    {
        $tags = new Tags();

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock
            ->expects(static::once())
            ->method('tags')
            ->willReturn($tags);

        $result = $this->resolver->resolve($editorialMock);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldContinueWhenTagFetchThrowsException(): void
    {
        $editorialTag1 = $this->createMock(EditorialTag::class);
        $editorialTag1->method('id')->willReturn('tag-1');

        $editorialTag2 = $this->createMock(EditorialTag::class);
        $editorialTag2->method('id')->willReturn('tag-2');

        $tags = new Tags();
        $tags->addItem($editorialTag1);
        $tags->addItem($editorialTag2);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock
            ->expects(static::once())
            ->method('tags')
            ->willReturn($tags);

        $resolvedTag = $this->createMock(Tag::class);

        $this->queryTagClient
            ->expects(static::exactly(2))
            ->method('findTagById')
            ->willReturnOnConsecutiveCalls(
                static::throwException(new \Exception('Tag not found')),
                $resolvedTag,
            );

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with('Tag not found');

        $result = $this->resolver->resolve($editorialMock);

        static::assertCount(1, $result);
        static::assertSame($resolvedTag, $result[0]);
    }
}
