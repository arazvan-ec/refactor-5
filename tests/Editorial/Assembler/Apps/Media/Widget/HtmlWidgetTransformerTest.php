<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Media\Widget;

use App\Editorial\Assembler\Apps\Media\Widget\HtmlWidgetTransformer;
use Ec\Widget\Domain\Model\HtmlWidget;
use Ec\Widget\Domain\Model\Widget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlWidgetTransformer::class)]
final class HtmlWidgetTransformerTest extends TestCase
{
    private HtmlWidgetTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new HtmlWidgetTransformer();
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenWidgetIsNotHtmlWidget(): void
    {
        $widget = $this->createMock(Widget::class);

        $result = $this->transformer->transform($widget);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnHtmlTypeFromSupports(): void
    {
        self::assertSame('html', $this->transformer->supports());
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $expectedResult
     */
    #[Test]
    #[DataProvider('provideHtmlWidgetData')]
    public function shouldTransformHtmlWidgetCorrectly(
        string $url,
        array $params,
        array $expectedResult,
    ): void {
        $htmlWidget = $this->createMock(HtmlWidget::class);
        $htmlWidget->method('url')->willReturn($url);
        $htmlWidget->method('params')->willReturn($params);

        $result = $this->transformer->transform($htmlWidget);

        self::assertSame($expectedResult['url'], $result['url']);
        self::assertSame($expectedResult['aspectRatio'], $result['aspectRatio']);
    }

    /**
     * @param array<string, mixed> $params
     */
    #[Test]
    #[DataProvider('provideAspectRatioData')]
    public function shouldCalculateAspectRatioCorrectly(
        array $params,
        ?float $expectedAspectRatio,
    ): void {
        $htmlWidget = $this->createMock(HtmlWidget::class);
        $htmlWidget->method('url')->willReturn('http://test.com');
        $htmlWidget->method('params')->willReturn($params);

        $result = $this->transformer->transform($htmlWidget);

        self::assertSame($expectedAspectRatio, $result['aspectRatio']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function provideHtmlWidgetData(): array
    {
        return [
            'complete-widget-with-aspect-ratio-4-3' => [
                'url' => 'https://www.elconfidencial.dev/lottery-service/591a7a1e',
                'params' => [
                    'overflow' => true,
                    'height' => 500,
                    'width' => '100%',
                    'aspect-ratio' => '4/3',
                    'class' => 'lotteryWidgetWrapper',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/lottery-service/591a7a1e',
                    'aspectRatio' => 1.3,
                ],
            ],
            'widget-with-aspect-ratio-16-9' => [
                'url' => 'https://www.elconfidencial.dev/video-widget/123',
                'params' => [
                    'overflow' => false,
                    'height' => 1080,
                    'width' => 1920,
                    'aspect-ratio' => '16/9',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/video-widget/123',
                    'aspectRatio' => 1.8,
                ],
            ],
            'widget-without-aspect-ratio' => [
                'url' => 'https://www.elconfidencial.dev/simple-widget/456',
                'params' => [
                    'overflow' => true,
                    'height' => 300,
                    'width' => '100%',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/simple-widget/456',
                    'aspectRatio' => null,
                ],
            ],
            'widget-with-square-aspect-ratio-1-1' => [
                'url' => 'https://www.elconfidencial.dev/square-widget/789',
                'params' => [
                    'aspect-ratio' => '1/1',
                    'class' => 'square',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/square-widget/789',
                    'aspectRatio' => 1.0,
                ],
            ],
            'widget-with-empty-url' => [
                'url' => '',
                'params' => [
                    'aspect-ratio' => '16/9',
                ],
                'expectedResult' => [
                    'url' => null,
                    'aspectRatio' => 1.8,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function provideAspectRatioData(): array
    {
        return [
            'aspect-ratio-4-3' => [
                'params' => ['aspect-ratio' => '4/3'],
                'expectedAspectRatio' => 1.3,
            ],
            'aspect-ratio-16-9' => [
                'params' => ['aspect-ratio' => '16/9'],
                'expectedAspectRatio' => 1.8,
            ],
            'aspect-ratio-1-1' => [
                'params' => ['aspect-ratio' => '1/1'],
                'expectedAspectRatio' => 1.0,
            ],
            'aspect-ratio-21-9' => [
                'params' => ['aspect-ratio' => '21/9'],
                'expectedAspectRatio' => 2.3,
            ],
            'aspect-ratio-9-16' => [
                'params' => ['aspect-ratio' => '9/16'],
                'expectedAspectRatio' => 0.6,
            ],
            'no-aspect-ratio' => [
                'params' => [],
                'expectedAspectRatio' => null,
            ],
            'empty-aspect-ratio' => [
                'params' => ['aspect-ratio' => ''],
                'expectedAspectRatio' => null,
            ],
            'invalid-aspect-ratio-no-slash' => [
                'params' => ['aspect-ratio' => '43'],
                'expectedAspectRatio' => null,
            ],
            'invalid-aspect-ratio-not-numeric' => [
                'params' => ['aspect-ratio' => 'a/b'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-division-by-zero' => [
                'params' => ['aspect-ratio' => '4/0'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-with-spaces' => [
                'params' => ['aspect-ratio' => ' 16 / 9 '],
                'expectedAspectRatio' => 1.8,
            ],
            'aspect-ratio-not-string' => [
                'params' => ['aspect-ratio' => 123],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-three-parts' => [
                'params' => ['aspect-ratio' => '4/3/2'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-with-multiple-slashes' => [
                'params' => ['aspect-ratio' => '4//3'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-decimal-strings' => [
                'params' => ['aspect-ratio' => '5.5/2.5'],
                'expectedAspectRatio' => 2.2,
            ],
            'aspect-ratio-large-values' => [
                'params' => ['aspect-ratio' => '20/5'],
                'expectedAspectRatio' => 4.0,
            ],
            'aspect-ratio-first-part-not-numeric' => [
                'params' => ['aspect-ratio' => 'abc/3'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-second-part-not-numeric' => [
                'params' => ['aspect-ratio' => '4/xyz'],
                'expectedAspectRatio' => null,
            ],
        ];
    }
}
