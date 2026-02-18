<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Media;

use App\Editorial\Assembler\Apps\Media\EmbedVideoTransformer;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmbedVideoTransformer::class)]
final class EmbedVideoTransformerTest extends TestCase
{
    private EmbedVideoTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new EmbedVideoTransformer();
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
        $opening->method('multimediaId')->willReturn('nonExistentId');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnGenericEmbedVideoForNonDailyMotionContent(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('id1');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('caption')->willReturn('Test Caption');
        $multimedia->method('html')
            ->willReturn('<iframe src="https://www.testmotion.com/embed/video/x7u5j5"></iframe>');

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertArrayHasKey('id', $result);
        self::assertSame('id1', $result['id']);
        self::assertSame('embedVideo', $result['type']);
        self::assertSame('Test Caption', $result['caption']);
        self::assertSame(
            '<iframe src="https://www.testmotion.com/embed/video/x7u5j5"></iframe>',
            $result['html']
        );
    }

    #[Test]
    public function shouldReturnDailyMotionEmbedVideoForDailyMotionContent(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('id1');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('caption')->willReturn('Test Caption');
        $multimedia->method('html')
            ->willReturn('<div itemscope itemtype="https://schema.org/VideoObject"><meta itemprop="name" content="prueba"><meta itemprop="description" content="asdasda"><meta itemprop="uploadDate" content="2025-08-29T12:00:16.000Z"><meta itemprop="thumbnailUrl" content="https://s2.dmcdn.net/v/Z0MUI1ekN8LWSfu6D/x180"><meta itemprop="duration" content="P5S"><meta itemprop="embedUrl" content="https://geo.dailymotion.com/player/x1i0xw.html?video=x9pnrf6"><script src="https://geo.dailymotion.com/player/x1i0xw.js" data-video="x9pnrf6"></script></div>');

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertArrayHasKey('id', $result);
        self::assertSame('id1', $result['id']);
        self::assertSame('embedVideoDailyMotion', $result['type']);
        self::assertSame('Test Caption', $result['caption']);
        self::assertSame('x1i0xw', $result['playerId']);
        self::assertSame('x9pnrf6', $result['videoId']);
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenDailyMotionRegexDoesNotMatch(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('id1');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('caption')->willReturn('Test Caption');
        $multimedia->method('html')
            ->willReturn('<div>dailymotion.com but no valid player URL</div>');

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldSupportMultimediaEmbedVideoClass(): void
    {
        self::assertSame(MultimediaEmbedVideo::class, $this->transformer->supports());
    }
}
