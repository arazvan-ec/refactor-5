<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Media;

use App\Editorial\Assembler\Apps\Media\MediaTransformer;
use App\Editorial\Assembler\Apps\Media\MediaTransformerPipeline;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Editorial\Exceptions\MultimediaDataTransformerNotFoundException;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaTransformerPipeline::class)]
final class MediaTransformerPipelineTest extends TestCase
{
    private MediaTransformerPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new MediaTransformerPipeline();
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMultimediaIdIsEmpty(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('');

        $result = $this->pipeline->transform([], $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMultimediaIdNotInData(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('nonExistentId');

        $result = $this->pipeline->transform([], $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldThrowExceptionWhenNoTransformerRegisteredForType(): void
    {
        $multimediaElement = $this->createMock(MultimediaEmbedVideo::class);
        $multimediaElement->method('type')->willReturn('mediaType');

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('multimediaId');

        $multimediaData = [
            'multimediaId' => [
                'opening' => $multimediaElement,
            ],
        ];

        $this->expectException(MultimediaDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('Media data transformer type mediaType not found');

        $this->pipeline->transform($multimediaData, $opening);
    }

    #[Test]
    public function shouldDelegateToRegisteredTransformerAndReturnResult(): void
    {
        $multimedia = $this->createMock(MultimediaEmbedVideo::class);

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('multimediaId');

        /** @var array<string, array{opening: MultimediaEmbedVideo}> $multimediaData */
        $multimediaData = [
            'multimediaId' => [
                'opening' => $multimedia,
            ],
        ];

        $expectedResult = [
            'id' => 'multimediaId',
            'type' => 'embedVideo',
            'caption' => 'Test Caption',
            'html' => '<iframe src="https://www.testmotion.com/embed/video/x7u5j5"></iframe>',
        ];

        $transformer = $this->createMock(MediaTransformer::class);
        $transformer->method('supports')->willReturn($multimedia::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with($multimediaData, $opening)
            ->willReturn($expectedResult);

        $this->pipeline->addTransformer($transformer);

        $result = $this->pipeline->transform($multimediaData, $opening);

        self::assertSame($expectedResult, $result);
    }

    #[Test]
    public function shouldOverwriteTransformerForSameClass(): void
    {
        $multimedia = $this->createMock(MultimediaEmbedVideo::class);

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('id1');

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $firstResult = ['type' => 'first'];
        $secondResult = ['type' => 'second'];

        $firstTransformer = $this->createMock(MediaTransformer::class);
        $firstTransformer->method('supports')->willReturn($multimedia::class);
        $firstTransformer->method('transform')->willReturn($firstResult);

        $secondTransformer = $this->createMock(MediaTransformer::class);
        $secondTransformer->method('supports')->willReturn($multimedia::class);
        $secondTransformer->method('transform')->willReturn($secondResult);

        $this->pipeline->addTransformer($firstTransformer);
        $this->pipeline->addTransformer($secondTransformer);

        $result = $this->pipeline->transform($multimediaData, $opening);

        self::assertSame($secondResult, $result);
    }
}
