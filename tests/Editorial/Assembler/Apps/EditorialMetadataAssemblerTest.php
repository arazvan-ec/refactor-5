<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\EditorialMetadataAssembler;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\EditorialTitles;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EditorialMetadataAssembler::class)]
final class EditorialMetadataAssemblerTest extends TestCase
{
    private EditorialMetadataAssembler $assembler;

    protected function setUp(): void
    {
        $this->assembler = new EditorialMetadataAssembler(
            new UrlGenerator('dev')
        );
    }

    #[Test]
    public function assembleShouldReturnCompleteEditorialMetadata(): void
    {
        $editorialId = $this->createMock(EditorialId::class);
        $editorialId->method('id')->willReturn('12345');

        $titles = $this->createMock(EditorialTitles::class);
        $titles->method('title')->willReturn('Test Title');
        $titles->method('preTitle')->willReturn('Pre Title');
        $titles->method('urlTitle')->willReturn('test-title');
        $titles->method('mobileTitle')->willReturn('Mobile Title');

        $body = $this->createMock(Body::class);
        $body->method('countWords')->willReturn(500);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('id')->willReturn($editorialId);
        $editorial->method('editorialTitles')->willReturn($titles);
        $editorial->method('lead')->willReturn('Lead text');
        $editorial->method('publicationDate')->willReturn(new \DateTime('2023-01-01 12:00:00'));
        $editorial->method('endOn')->willReturn(new \DateTime('2023-01-02 12:00:00'));
        $editorial->method('editorialType')->willReturn('news');
        $editorial->method('indexed')->willReturn(true);
        $editorial->method('isDeleted')->willReturn(false);
        $editorial->method('isPublished')->willReturn(true);
        $editorial->method('closingModeId')->willReturn('1');
        $editorial->method('canComment')->willReturn(true);
        $editorial->method('isBrand')->willReturn(false);
        $editorial->method('isAmazonOnsite')->willReturn(false);
        $editorial->method('contentType')->willReturn('article');
        $editorial->method('canonicalEditorialId')->willReturn('54321');
        $editorial->method('urlDate')->willReturn(new \DateTime('2023-01-01 12:00:00'));
        $editorial->method('body')->willReturn($body);

        $section = $this->createMock(Section::class);
        $section->method('getPath')->willReturn('espana');
        $section->method('isSubdomainBlog')->willReturn(false);
        $section->method('siteId')->willReturn('1');

        $result = $this->assembler->assemble($editorial, $section);

        static::assertSame('12345', $result['id']);
        static::assertSame('Lead text', $result['lead']);
        static::assertSame('2023-01-01 12:00:00', $result['publicationDate']);
        static::assertSame('2023-01-01 12:00:00', $result['updatedOn']);
        static::assertSame('2023-01-02 12:00:00', $result['endOn']);
        static::assertSame('registry', $result['closingModeId']);
        static::assertTrue($result['indexable']);
        static::assertFalse($result['deleted']);
        static::assertTrue($result['published']);
        static::assertTrue($result['commentable']);
        static::assertFalse($result['isBrand']);
        static::assertFalse($result['isAmazonOnsite']);
        static::assertSame('article', $result['contentType']);
        static::assertSame('54321', $result['canonicalEditorialId']);
        static::assertSame('2023-01-01 12:00:00', $result['urlDate']);
        static::assertSame(500, $result['countWords']);
    }

    #[Test]
    public function assembleShouldReturnCorrectUrlStructure(): void
    {
        $editorialId = $this->createMock(EditorialId::class);
        $editorialId->method('id')->willReturn('99');

        $titles = $this->createMock(EditorialTitles::class);
        $titles->method('title')->willReturn('Title');
        $titles->method('preTitle')->willReturn('');
        $titles->method('urlTitle')->willReturn('my-article');
        $titles->method('mobileTitle')->willReturn('');

        $body = $this->createMock(Body::class);
        $body->method('countWords')->willReturn(100);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('id')->willReturn($editorialId);
        $editorial->method('editorialTitles')->willReturn($titles);
        $editorial->method('lead')->willReturn('');
        $editorial->method('publicationDate')->willReturn(new \DateTime('2023-06-15'));
        $editorial->method('endOn')->willReturn(new \DateTime('2023-06-16'));
        $editorial->method('editorialType')->willReturn('news');
        $editorial->method('indexed')->willReturn(true);
        $editorial->method('isDeleted')->willReturn(false);
        $editorial->method('isPublished')->willReturn(true);
        $editorial->method('closingModeId')->willReturn('1');
        $editorial->method('canComment')->willReturn(false);
        $editorial->method('isBrand')->willReturn(false);
        $editorial->method('isAmazonOnsite')->willReturn(false);
        $editorial->method('contentType')->willReturn('article');
        $editorial->method('canonicalEditorialId')->willReturn('');
        $editorial->method('urlDate')->willReturn(new \DateTime('2023-06-15'));
        $editorial->method('body')->willReturn($body);

        $section = $this->createMock(Section::class);
        $section->method('getPath')->willReturn('espana/politica');
        $section->method('isSubdomainBlog')->willReturn(false);
        $section->method('siteId')->willReturn('1');

        $result = $this->assembler->assemble($editorial, $section);

        static::assertStringContainsString('espana/politica', $result['url']);
        static::assertStringContainsString('2023-06-15', $result['url']);
        static::assertStringContainsString('my-article_99', $result['url']);
    }

    #[Test]
    public function assembleShouldReturnCorrectTitlesStructure(): void
    {
        $editorialId = $this->createMock(EditorialId::class);
        $editorialId->method('id')->willReturn('1');

        $titles = $this->createMock(EditorialTitles::class);
        $titles->method('title')->willReturn('Main Title');
        $titles->method('preTitle')->willReturn('Pre');
        $titles->method('urlTitle')->willReturn('main-title');
        $titles->method('mobileTitle')->willReturn('Short');

        $body = $this->createMock(Body::class);
        $body->method('countWords')->willReturn(10);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('id')->willReturn($editorialId);
        $editorial->method('editorialTitles')->willReturn($titles);
        $editorial->method('lead')->willReturn('');
        $editorial->method('publicationDate')->willReturn(new \DateTime());
        $editorial->method('endOn')->willReturn(new \DateTime());
        $editorial->method('editorialType')->willReturn('news');
        $editorial->method('indexed')->willReturn(true);
        $editorial->method('isDeleted')->willReturn(false);
        $editorial->method('isPublished')->willReturn(true);
        $editorial->method('closingModeId')->willReturn('1');
        $editorial->method('canComment')->willReturn(false);
        $editorial->method('isBrand')->willReturn(false);
        $editorial->method('isAmazonOnsite')->willReturn(false);
        $editorial->method('contentType')->willReturn('article');
        $editorial->method('canonicalEditorialId')->willReturn('');
        $editorial->method('urlDate')->willReturn(new \DateTime());
        $editorial->method('body')->willReturn($body);

        $section = $this->createMock(Section::class);

        $result = $this->assembler->assemble($editorial, $section);

        static::assertSame('Main Title', $result['titles']['title']);
        static::assertSame('Pre', $result['titles']['preTitle']);
        static::assertSame('main-title', $result['titles']['urlTitle']);
        static::assertSame('Short', $result['titles']['mobileTitle']);
    }

    #[Test]
    public function assembleShouldReturnCorrectTypeStructure(): void
    {
        $editorialId = $this->createMock(EditorialId::class);
        $editorialId->method('id')->willReturn('1');

        $titles = $this->createMock(EditorialTitles::class);

        $body = $this->createMock(Body::class);
        $body->method('countWords')->willReturn(10);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('id')->willReturn($editorialId);
        $editorial->method('editorialTitles')->willReturn($titles);
        $editorial->method('lead')->willReturn('');
        $editorial->method('publicationDate')->willReturn(new \DateTime());
        $editorial->method('endOn')->willReturn(new \DateTime());
        $editorial->method('editorialType')->willReturn('blog');
        $editorial->method('indexed')->willReturn(true);
        $editorial->method('isDeleted')->willReturn(false);
        $editorial->method('isPublished')->willReturn(true);
        $editorial->method('closingModeId')->willReturn('2');
        $editorial->method('canComment')->willReturn(false);
        $editorial->method('isBrand')->willReturn(false);
        $editorial->method('isAmazonOnsite')->willReturn(false);
        $editorial->method('contentType')->willReturn('article');
        $editorial->method('canonicalEditorialId')->willReturn('');
        $editorial->method('urlDate')->willReturn(new \DateTime());
        $editorial->method('body')->willReturn($body);

        $section = $this->createMock(Section::class);

        $result = $this->assembler->assemble($editorial, $section);

        static::assertSame('3', $result['type']['id']);
        static::assertSame('blog', $result['type']['name']);
        static::assertSame('payment', $result['closingModeId']);
    }

    #[Test]
    public function assembleShouldUseBlogSubdomainForBlogSections(): void
    {
        $editorialId = $this->createMock(EditorialId::class);
        $editorialId->method('id')->willReturn('1');

        $titles = $this->createMock(EditorialTitles::class);
        $titles->method('urlTitle')->willReturn('title');

        $body = $this->createMock(Body::class);
        $body->method('countWords')->willReturn(10);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('id')->willReturn($editorialId);
        $editorial->method('editorialTitles')->willReturn($titles);
        $editorial->method('lead')->willReturn('');
        $editorial->method('publicationDate')->willReturn(new \DateTime('2023-01-01'));
        $editorial->method('endOn')->willReturn(new \DateTime());
        $editorial->method('editorialType')->willReturn('news');
        $editorial->method('indexed')->willReturn(true);
        $editorial->method('isDeleted')->willReturn(false);
        $editorial->method('isPublished')->willReturn(true);
        $editorial->method('closingModeId')->willReturn('1');
        $editorial->method('canComment')->willReturn(false);
        $editorial->method('isBrand')->willReturn(false);
        $editorial->method('isAmazonOnsite')->willReturn(false);
        $editorial->method('contentType')->willReturn('article');
        $editorial->method('canonicalEditorialId')->willReturn('');
        $editorial->method('urlDate')->willReturn(new \DateTime());
        $editorial->method('body')->willReturn($body);

        $section = $this->createMock(Section::class);
        $section->method('getPath')->willReturn('blogs/my-blog');
        $section->method('isSubdomainBlog')->willReturn(true);
        $section->method('siteId')->willReturn('1');

        $result = $this->assembler->assemble($editorial, $section);

        static::assertStringStartsWith('https://blog.elconfidencial.dev/', $result['url']);
    }
}
