<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Media\Widget;

use App\Editorial\Assembler\Apps\Media\Widget\WidgetTransformerPipeline;
use App\Editorial\Assembler\Apps\Media\Widget\WidgetTypeTransformer;
use Ec\Widget\Domain\Model\Widget;
use Ec\Widget\Exceptions\WidgetDataTransformerAlreadyExistsException;
use Ec\Widget\Exceptions\WidgetDataTransformerNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(WidgetTransformerPipeline::class)]
final class WidgetTransformerPipelineTest extends TestCase
{
    private WidgetTransformerPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new WidgetTransformerPipeline();
    }

    #[Test]
    public function shouldAddTransformerSuccessfully(): void
    {
        $transformer = $this->createMock(WidgetTypeTransformer::class);
        $transformer->method('supports')->willReturn('html');

        $this->pipeline->addTransformer($transformer);

        $widget = $this->createMock(Widget::class);
        $widget->method('type')->willReturn('html');

        $transformer->method('transform')->with($widget)->willReturn(['type' => 'html']);

        $result = $this->pipeline->transform($widget);

        self::assertSame(['type' => 'html'], $result);
    }

    #[Test]
    public function shouldThrowExceptionWhenAddingDuplicateTransformer(): void
    {
        /** @var WidgetTypeTransformer&MockObject $transformer1 */
        $transformer1 = $this->createMock(WidgetTypeTransformer::class);
        $transformer1->method('supports')->willReturn('html');

        /** @var WidgetTypeTransformer&MockObject $transformer2 */
        $transformer2 = $this->createMock(WidgetTypeTransformer::class);
        $transformer2->method('supports')->willReturn('html');

        $this->pipeline->addTransformer($transformer1);

        $this->expectException(WidgetDataTransformerAlreadyExistsException::class);
        $this->expectExceptionMessage('Data transformer for widget type html already exists');

        $this->pipeline->addTransformer($transformer2);
    }

    #[Test]
    public function shouldExecuteCorrectTransformerBasedOnWidgetType(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget->method('type')->willReturn('html');

        $expectedResult = [
            'url' => 'https://example.com',
            'aspectRatio' => 1.33,
        ];

        $transformer = $this->createMock(WidgetTypeTransformer::class);
        $transformer->method('supports')->willReturn('html');
        $transformer->expects($this->once())
            ->method('transform')
            ->with($widget)
            ->willReturn($expectedResult);

        $this->pipeline->addTransformer($transformer);

        $result = $this->pipeline->transform($widget);

        self::assertSame($expectedResult, $result);
    }

    #[Test]
    public function shouldThrowExceptionWhenNoTransformerFoundForWidgetType(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget->method('type')->willReturn('unknown');

        $this->expectException(WidgetDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('No data transformer found for widget type unknown');

        $this->pipeline->transform($widget);
    }

    #[Test]
    public function shouldThrowExceptionWhenWidgetTypeIsEmpty(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget->method('type')->willReturn('');

        $this->expectException(WidgetDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('No data transformer found for widget type unknown');

        $this->pipeline->transform($widget);
    }

    #[Test]
    public function shouldHandleMultipleTransformers(): void
    {
        $htmlWidget = $this->createMock(Widget::class);
        $htmlWidget->method('type')->willReturn('html');

        $lotteryWidget = $this->createMock(Widget::class);
        $lotteryWidget->method('type')->willReturn('lottery');

        $htmlTransformer = $this->createMock(WidgetTypeTransformer::class);
        $htmlTransformer->method('supports')->willReturn('html');
        $htmlTransformer->method('transform')->willReturn(['type' => 'html']);

        $lotteryTransformer = $this->createMock(WidgetTypeTransformer::class);
        $lotteryTransformer->method('supports')->willReturn('lottery');
        $lotteryTransformer->method('transform')->willReturn(['type' => 'lottery']);

        $this->pipeline->addTransformer($htmlTransformer);
        $this->pipeline->addTransformer($lotteryTransformer);

        $htmlResult = $this->pipeline->transform($htmlWidget);
        $lotteryResult = $this->pipeline->transform($lotteryWidget);

        self::assertSame(['type' => 'html'], $htmlResult);
        self::assertSame(['type' => 'lottery'], $lotteryResult);
    }
}
