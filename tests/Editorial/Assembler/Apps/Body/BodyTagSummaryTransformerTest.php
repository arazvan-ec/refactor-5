<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagSummaryTransformer;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyTagSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagSummaryTransformer::class)]
final class BodyTagSummaryTransformerTest extends TestCase
{
    private BodyTagSummaryTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new BodyTagSummaryTransformer();
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
    public function supportsShouldReturnBodyTagSummaryClass(): void
    {
        static::assertSame(BodyTagSummary::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArray(): void
    {
        $bodyElementMock = $this->createConfiguredMock(BodyTagSummary::class, [
            'type' => 'bodytagsummary',
            'content' => 'content',
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'bodytagsummary',
            'content' => 'content',
        ], $result);
    }
}
