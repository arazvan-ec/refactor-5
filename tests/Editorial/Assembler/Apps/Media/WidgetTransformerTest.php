<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Media;

use App\Editorial\Assembler\Apps\Media\Widget\WidgetTransformerPipeline;
use App\Editorial\Assembler\Apps\Media\WidgetTransformer;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaWidget;
use Ec\Widget\Domain\Model\EveryWidget;
use Ec\Widget\Domain\Model\Widget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(WidgetTransformer::class)]
final class WidgetTransformerTest extends TestCase
{
    private WidgetTransformer $transformer;
    private WidgetTransformerPipeline&MockObject $widgetPipeline;

    protected function setUp(): void
    {
        $this->widgetPipeline = $this->createMock(WidgetTransformerPipeline::class);
        $this->transformer = new WidgetTransformer($this->widgetPipeline);
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMultimediaIdIsEmpty(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('');

        $result = $this->transformer->transform([], $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMultimediaIdNotInData(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('nonexistent-id');

        $multimedia = $this->createMock(MultimediaWidget::class);
        $widget = $this->createMock(EveryWidget::class);

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
                'resource' => $widget,
            ],
        ];

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldSupportMultimediaWidgetClass(): void
    {
        self::assertSame(MultimediaWidget::class, $this->transformer->supports());
    }

    /**
     * @param array<string, mixed> $specificWidgetData
     * @param array<string, mixed> $expectedResult
     */
    #[Test]
    #[DataProvider('provideWidgetData')]
    public function shouldTransformMultimediaWidgetCorrectly(
        string $multimediaId,
        string $caption,
        array $specificWidgetData,
        array $expectedResult,
    ): void {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn($multimediaId);

        $multimedia = $this->createMock(MultimediaWidget::class);
        $multimedia->method('caption')->willReturn($caption);

        $widget = $this->createMock(Widget::class);

        $multimediaData = [
            $multimediaId => [
                'opening' => $multimedia,
                'resource' => $widget,
            ],
        ];

        $this->widgetPipeline
            ->expects($this->once())
            ->method('transform')
            ->with($widget)
            ->willReturn($specificWidgetData);

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertSame($expectedResult['type'], $result['type']);
        self::assertSame($expectedResult['caption'], $result['caption']);

        foreach ($specificWidgetData as $key => $value) {
            self::assertArrayHasKey($key, $result);
            self::assertSame($value, $result[$key]);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function provideWidgetData(): array
    {
        return [
            'html-widget-complete' => [
                'multimediaId' => 'widget-123',
                'caption' => 'Lottery Widget 2026',
                'specificWidgetData' => [
                    'url' => 'https://www.elconfidencial.dev/lottery-service/591a7a1e',
                    'aspectRatio' => 1.3,
                ],
                'expectedResult' => [
                    'type' => 'widget',
                    'caption' => 'Lottery Widget 2026',
                ],
            ],
            'html-widget-simple' => [
                'multimediaId' => 'widget-456',
                'caption' => 'Widget Simple',
                'specificWidgetData' => [
                    'url' => 'https://www.elconfidencial.dev/simple-widget/456',
                    'aspectRatio' => null,
                ],
                'expectedResult' => [
                    'type' => 'widget',
                    'caption' => 'Widget Simple',
                ],
            ],
            'widget-with-empty-caption' => [
                'multimediaId' => 'widget-789',
                'caption' => '',
                'specificWidgetData' => [
                    'url' => 'https://www.elconfidencial.dev/widget/789',
                    'aspectRatio' => 1.8,
                ],
                'expectedResult' => [
                    'type' => 'widget',
                    'caption' => '',
                ],
            ],
        ];
    }
}
