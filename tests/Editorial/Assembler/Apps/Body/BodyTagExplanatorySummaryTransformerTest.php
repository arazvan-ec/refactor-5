<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagExplanatorySummaryTransformer;
use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyNormal;
use Ec\Editorial\Domain\Model\Body\BodyTagExplanatorySummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagExplanatorySummaryTransformer::class)]
final class BodyTagExplanatorySummaryTransformerTest extends TestCase
{
    private BodyTagExplanatorySummaryTransformer $transformer;
    /** @var BodyTransformerPipeline&MockObject */
    private BodyTransformerPipeline $pipeline;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->pipeline = $this->createMock(BodyTransformerPipeline::class);
        $this->transformer = new BodyTagExplanatorySummaryTransformer($this->pipeline);
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
    public function supportsShouldReturnBodyTagExplanatorySummaryClass(): void
    {
        static::assertSame(BodyTagExplanatorySummary::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArray(): void
    {
        $bodyNormalMock = $this->createMock(BodyNormal::class);

        $bodyElementMock = $this->createMock(BodyTagExplanatorySummary::class);
        $bodyElementMock->expects(static::once())
            ->method('body')
            ->willReturn($bodyNormalMock);
        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytagexplanatorysummary');
        $bodyElementMock->expects(static::once())
            ->method('title')
            ->willReturn('this is a title for bodytagexplanatorysummary');

        $this->pipeline->expects(static::once())
            ->method('transformBody')
            ->with($bodyNormalMock, $this->context)
            ->willReturn([
                'type' => '',
                'elements' => [],
            ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'bodytagexplanatorysummary',
            'title' => 'this is a title for bodytagexplanatorysummary',
            'items' => [],
        ], $result);
    }
}
