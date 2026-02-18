<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\SubHeadTransformer;
use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\LinkExtractor;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\SubHead;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SubHeadTransformer::class)]
final class SubHeadTransformerTest extends TestCase
{
    private SubHeadTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new SubHeadTransformer(new LinkExtractor());
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
    public function supportsShouldReturnSubHeadClass(): void
    {
        static::assertSame(SubHead::class, $this->transformer->supports());
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

        $bodyElementMock = $this->createConfiguredMock(SubHead::class, [
            'type' => 'subhead',
            'content' => 'Contenido #1, con links',
            'links' => [$linkMock],
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'subhead',
            'content' => 'Contenido #1, con links',
            'links' => [$expectedLink],
        ], $result);
    }

    #[Test]
    public function transformShouldReturnNullLinksWhenEmpty(): void
    {
        $bodyElementMock = $this->createConfiguredMock(SubHead::class, [
            'type' => 'subhead',
            'content' => 'Contenido #1, sin links',
            'links' => [],
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'subhead',
            'content' => 'Contenido #1, sin links',
            'links' => null,
        ], $result);
    }
}
