<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\LinkTransformer;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\Link;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinkTransformer::class)]
final class LinkTransformerTest extends TestCase
{
    private LinkTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new LinkTransformer();
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
    public function supportsShouldReturnLinkClass(): void
    {
        static::assertSame(Link::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArray(): void
    {
        $bodyElementMock = $this->createConfiguredMock(Link::class, [
            'type' => 'link',
            'content' => 'content',
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'link',
            'content' => 'content',
        ], $result);
    }
}
