<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyElementTransformer;
use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyNormal;
use Ec\Editorial\Domain\Model\Body\Paragraph;
use Ec\Editorial\Domain\Model\Body\SubHead;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(BodyTransformerPipeline::class)]
final class BodyTransformerPipelineTest extends TestCase
{
    private BodyTransformerPipeline $pipeline;
    private LoggerInterface $logger;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pipeline = new BodyTransformerPipeline($this->logger);
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
    public function transformElementShouldDispatchToCorrectTransformer(): void
    {
        $paragraphMock = $this->createMock(Paragraph::class);
        $expectedResult = ['type' => 'paragraph', 'content' => 'test'];

        $transformer = $this->createMock(BodyElementTransformer::class);
        $transformer->method('supports')->willReturn(Paragraph::class);
        $transformer->expects(static::once())
            ->method('transform')
            ->with($paragraphMock, $this->context)
            ->willReturn($expectedResult);

        $this->pipeline->addTransformer($transformer);

        $result = $this->pipeline->transformElement($paragraphMock, $this->context);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function transformElementShouldThrowWhenNoTransformerFound(): void
    {
        $paragraphMock = $this->createMock(Paragraph::class);
        $paragraphMock->method('type')->willReturn('paragraph');

        $this->expectException(BodyDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('BodyElement data transformer type paragraph not found');

        $this->pipeline->transformElement($paragraphMock, $this->context);
    }

    #[Test]
    public function transformBodyShouldReturnAllElements(): void
    {
        $paragraphMock = $this->createMock(Paragraph::class);
        $subHeadMock = $this->createMock(SubHead::class);

        $bodyMock = $this->createMock(BodyNormal::class);
        $bodyMock->method('type')->willReturn('body');
        $bodyMock->method('getArrayCopy')->willReturn([$paragraphMock, $subHeadMock]);

        $paragraphTransformer = $this->createMock(BodyElementTransformer::class);
        $paragraphTransformer->method('supports')->willReturn(Paragraph::class);
        $paragraphTransformer->method('transform')->willReturn(['type' => 'paragraph', 'content' => 'p1']);

        $subHeadTransformer = $this->createMock(BodyElementTransformer::class);
        $subHeadTransformer->method('supports')->willReturn(SubHead::class);
        $subHeadTransformer->method('transform')->willReturn(['type' => 'subhead', 'content' => 's1']);

        $this->pipeline->addTransformer($paragraphTransformer);
        $this->pipeline->addTransformer($subHeadTransformer);

        $result = $this->pipeline->transformBody($bodyMock, $this->context);

        static::assertSame('body', $result['type']);
        static::assertCount(2, $result['elements']);
        static::assertSame('paragraph', $result['elements'][0]['type']);
        static::assertSame('subhead', $result['elements'][1]['type']);
    }

    #[Test]
    public function transformBodyShouldSkipUnknownElementsAndLog(): void
    {
        $paragraphMock = $this->createMock(Paragraph::class);
        $unknownMock = $this->createMock(BodyElement::class);
        $unknownMock->method('type')->willReturn('unknown');

        $bodyMock = $this->createMock(BodyNormal::class);
        $bodyMock->method('type')->willReturn('body');
        $bodyMock->method('getArrayCopy')->willReturn([$unknownMock, $paragraphMock]);

        $paragraphTransformer = $this->createMock(BodyElementTransformer::class);
        $paragraphTransformer->method('supports')->willReturn(Paragraph::class);
        $paragraphTransformer->method('transform')->willReturn(['type' => 'paragraph', 'content' => 'p1']);

        $this->pipeline->addTransformer($paragraphTransformer);

        $this->logger->expects(static::once())
            ->method('info')
            ->with('BodyElement data transformer type unknown not found');

        $result = $this->pipeline->transformBody($bodyMock, $this->context);

        static::assertCount(1, $result['elements']);
        static::assertSame('paragraph', $result['elements'][0]['type']);
    }

    #[Test]
    public function addTransformerShouldRegisterBySupportsKey(): void
    {
        $transformer1 = $this->createMock(BodyElementTransformer::class);
        $transformer1->method('supports')->willReturn(Paragraph::class);

        $transformer2 = $this->createMock(BodyElementTransformer::class);
        $transformer2->method('supports')->willReturn(SubHead::class);

        $this->pipeline->addTransformer($transformer1);
        $this->pipeline->addTransformer($transformer2);

        $paragraphMock = $this->createMock(Paragraph::class);
        $transformer1->expects(static::once())
            ->method('transform')
            ->willReturn(['type' => 'paragraph']);

        $this->pipeline->transformElement($paragraphMock, $this->context);
    }
}
