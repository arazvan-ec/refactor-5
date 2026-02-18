<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\SectionAssembler;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Section\Domain\Model\Section;
use Ec\Section\Domain\Model\SectionId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SectionAssembler::class)]
final class SectionAssemblerTest extends TestCase
{
    private SectionAssembler $assembler;

    protected function setUp(): void
    {
        $this->assembler = new SectionAssembler(
            new UrlGenerator('dev')
        );
    }

    #[Test]
    public function assembleSectionShouldReturnCorrectStructure(): void
    {
        $sectionId = $this->createMock(SectionId::class);
        $sectionId->method('id')->willReturn('section-1');

        $section = $this->createMock(Section::class);
        $section->method('id')->willReturn($sectionId);
        $section->method('name')->willReturn('Section Name');
        $section->method('getPath')->willReturn('section-path');
        $section->method('siteId')->willReturn('1');
        $section->method('isSubdomainBlog')->willReturn(false);
        $section->method('encodeName')->willReturn('espana');

        $result = $this->assembler->assembleSection($section);

        static::assertSame('section-1', $result['id']);
        static::assertSame('Section Name', $result['name']);
        static::assertSame('https://www.elconfidencial.dev/section-path', $result['url']);
        static::assertSame('espana', $result['encodeName']);
    }

    #[Test]
    public function assembleSectionShouldUseBlogSubdomain(): void
    {
        $sectionId = $this->createMock(SectionId::class);
        $sectionId->method('id')->willReturn('blog-1');

        $section = $this->createMock(Section::class);
        $section->method('id')->willReturn($sectionId);
        $section->method('name')->willReturn('Blog Section');
        $section->method('getPath')->willReturn('blogs/my-blog');
        $section->method('siteId')->willReturn('1');
        $section->method('isSubdomainBlog')->willReturn(true);
        $section->method('encodeName')->willReturn('blog');

        $result = $this->assembler->assembleSection($section);

        static::assertSame('https://blog.elconfidencial.dev/blogs/my-blog', $result['url']);
    }

    #[Test]
    public function assembleOptionsShouldReturnSingleSectionWhenNoParent(): void
    {
        $sectionId = $this->createMock(SectionId::class);
        $sectionId->method('id')->willReturn('s-1');

        $section = $this->createMock(Section::class);
        $section->method('id')->willReturn($sectionId);
        $section->method('name')->willReturn('Root');
        $section->method('getPath')->willReturn('root-path');
        $section->method('siteId')->willReturn('1');
        $section->method('isSubdomainBlog')->willReturn(false);
        $section->method('encodeName')->willReturn('root');
        $section->method('parent')->willReturn(null);

        $result = $this->assembler->assembleOptions($section);

        static::assertCount(1, $result);
        static::assertSame('s-1', $result[0]['id']);
    }

    #[Test]
    public function assembleOptionsShouldReturnHierarchyInReverseOrder(): void
    {
        $parentId = $this->createMock(SectionId::class);
        $parentId->method('id')->willReturn('parent-1');

        $parentSection = $this->createMock(Section::class);
        $parentSection->method('id')->willReturn($parentId);
        $parentSection->method('name')->willReturn('Parent');
        $parentSection->method('getPath')->willReturn('parent');
        $parentSection->method('siteId')->willReturn('1');
        $parentSection->method('isSubdomainBlog')->willReturn(false);
        $parentSection->method('encodeName')->willReturn('parent');
        $parentSection->method('parent')->willReturn(null);

        $childId = $this->createMock(SectionId::class);
        $childId->method('id')->willReturn('child-1');

        $childSection = $this->createMock(Section::class);
        $childSection->method('id')->willReturn($childId);
        $childSection->method('name')->willReturn('Child');
        $childSection->method('getPath')->willReturn('parent/child');
        $childSection->method('siteId')->willReturn('1');
        $childSection->method('isSubdomainBlog')->willReturn(false);
        $childSection->method('encodeName')->willReturn('child');
        $childSection->method('parent')->willReturn($parentSection);

        $result = $this->assembler->assembleOptions($childSection);

        static::assertCount(2, $result);
        static::assertSame('parent-1', $result[0]['id']);
        static::assertSame('child-1', $result[1]['id']);
    }
}
