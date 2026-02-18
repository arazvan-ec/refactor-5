<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagHtmlTransformer;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyTagHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagHtmlTransformer::class)]
final class BodyTagHtmlTransformerTest extends TestCase
{
    private BodyTagHtmlTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new BodyTagHtmlTransformer();
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
    public function supportsShouldReturnBodyTagHtmlClass(): void
    {
        static::assertSame(BodyTagHtml::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArray(): void
    {
        $bodyElementMock = $this->createConfiguredMock(BodyTagHtml::class, [
            'type' => 'bodytaghtml',
            'content' => 'content',
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'bodytaghtml',
            'content' => 'content',
        ], $result);
    }
}
