<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\ParagraphTransformer;
use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\LinkExtractor;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\Paragraph;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParagraphTransformer::class)]
final class ParagraphTransformerTest extends TestCase
{
    private ParagraphTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new ParagraphTransformer(new LinkExtractor());
        $this->context = new BodyTransformContext(
            multimedia: [],
            multimediaOpening: [],
            insertedNews: [],
            recommendedEditorials: [],
            bodyPhotos: [],
            membershipLinks: [],
        );
    }

    #[Test]
    public function supportsShouldReturnParagraphClass(): void
    {
        static::assertSame(Paragraph::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArrayWithLinks(): void
    {
        $expectedLink = [
            'type' => 'link',
            'content' => 'links',
            'url' => 'https://www.elconfidencial.com/',
            'target' => '_self',
        ];

        $linkMock = $this->createConfiguredMock(Link::class, $expectedLink);

        $bodyElementMock = $this->createConfiguredMock(Paragraph::class, [
            'type' => 'paragraph',
            'content' => 'Contenido #1, con links',
            'links' => [$linkMock],
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'paragraph',
            'content' => 'Contenido #1, con links',
            'links' => [$expectedLink],
        ], $result);
    }

    #[Test]
    public function transformShouldReturnNullLinksWhenEmpty(): void
    {
        $bodyElementMock = $this->createConfiguredMock(Paragraph::class, [
            'type' => 'paragraph',
            'content' => 'Contenido #1, sin links',
            'links' => [],
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'paragraph',
            'content' => 'Contenido #1, sin links',
            'links' => null,
        ], $result);
    }
}
