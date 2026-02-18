<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\TagsAssembler;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;
use Ec\Tag\Domain\Model\TagId;
use Ec\Tag\Domain\Model\TagType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TagsAssembler::class)]
final class TagsAssemblerTest extends TestCase
{
    private TagsAssembler $assembler;

    protected function setUp(): void
    {
        $this->assembler = new TagsAssembler(
            new UrlGenerator('dev')
        );
    }

    #[Test]
    public function assembleShouldReturnEmptyArrayWhenNoTags(): void
    {
        $section = $this->createMock(Section::class);

        $result = $this->assembler->assemble([], $section);

        static::assertSame([], $result);
    }

    #[Test]
    public function assembleShouldReturnCorrectTagStructure(): void
    {
        $tagId = $this->createMock(TagId::class);
        $tagId->method('id')->willReturn('tagId');

        $tagType = $this->createMock(TagType::class);
        $tagType->method('name')->willReturn('Type Name');

        $tag = $this->createMock(Tag::class);
        $tag->method('id')->willReturn($tagId);
        $tag->method('name')->willReturn('Tag Name');
        $tag->method('type')->willReturn($tagType);

        $section = $this->createMock(Section::class);
        $section->method('siteId')->willReturn('1');

        $result = $this->assembler->assemble([$tag], $section);

        static::assertCount(1, $result);
        static::assertSame('tagId', $result[0]['id']);
        static::assertSame('Tag Name', $result[0]['name']);
        static::assertSame(
            'https://www.elconfidencial.dev/tags/type-name/tag-name-tagId',
            $result[0]['url']
        );
    }

    #[Test]
    public function assembleShouldReturnMultipleTags(): void
    {
        $tagId1 = $this->createMock(TagId::class);
        $tagId1->method('id')->willReturn('id1');

        $tagType1 = $this->createMock(TagType::class);
        $tagType1->method('name')->willReturn('Subject');

        $tag1 = $this->createMock(Tag::class);
        $tag1->method('id')->willReturn($tagId1);
        $tag1->method('name')->willReturn('First Tag');
        $tag1->method('type')->willReturn($tagType1);

        $tagId2 = $this->createMock(TagId::class);
        $tagId2->method('id')->willReturn('id2');

        $tagType2 = $this->createMock(TagType::class);
        $tagType2->method('name')->willReturn('Location');

        $tag2 = $this->createMock(Tag::class);
        $tag2->method('id')->willReturn($tagId2);
        $tag2->method('name')->willReturn('Second Tag');
        $tag2->method('type')->willReturn($tagType2);

        $section = $this->createMock(Section::class);
        $section->method('siteId')->willReturn('1');

        $result = $this->assembler->assemble([$tag1, $tag2], $section);

        static::assertCount(2, $result);
        static::assertSame('id1', $result[0]['id']);
        static::assertSame('id2', $result[1]['id']);
    }

    #[Test]
    public function assembleShouldUseCorrectSiteId(): void
    {
        $tagId = $this->createMock(TagId::class);
        $tagId->method('id')->willReturn('t1');

        $tagType = $this->createMock(TagType::class);
        $tagType->method('name')->willReturn('Type');

        $tag = $this->createMock(Tag::class);
        $tag->method('id')->willReturn($tagId);
        $tag->method('name')->willReturn('Tag');
        $tag->method('type')->willReturn($tagType);

        $section = $this->createMock(Section::class);
        $section->method('siteId')->willReturn('2');

        $result = $this->assembler->assemble([$tag], $section);

        static::assertStringContainsString('vanitatis.elconfidencial.dev', $result[0]['url']);
    }
}
