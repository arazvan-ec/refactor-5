<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\Media\MediaTransformerPipeline;
use App\Editorial\Assembler\Apps\MultimediaAssembler;
use App\Infrastructure\Service\MultimediaImageService;
use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Clipping;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultimediaAssembler::class)]
final class MultimediaAssemblerTest extends TestCase
{
    private MultimediaAssembler $assembler;
    private MockObject&MediaTransformerPipeline $mediaTransformerPipeline;
    private MockObject&MultimediaImageService $multimediaImageService;
    private MockObject&Thumbor $thumbor;

    protected function setUp(): void
    {
        $this->mediaTransformerPipeline = $this->createMock(MediaTransformerPipeline::class);
        $this->multimediaImageService = $this->createMock(MultimediaImageService::class);
        $this->thumbor = $this->createMock(Thumbor::class);

        $this->assembler = new MultimediaAssembler(
            $this->mediaTransformerPipeline,
            $this->multimediaImageService,
            $this->thumbor
        );
    }

    #[Test]
    public function assembleOpeningShouldReturnNullWhenEmpty(): void
    {
        $opening = $this->createMock(Opening::class);

        $result = $this->assembler->assembleOpening([], $opening);

        static::assertNull($result);
    }

    #[Test]
    public function assembleOpeningShouldDelegateToMediaPipeline(): void
    {
        $opening = $this->createMock(Opening::class);
        $multimediaData = ['mm-1' => ['opening' => 'data']];
        $expected = ['id' => '123', 'type' => 'photo'];

        $this->mediaTransformerPipeline->expects(static::once())
            ->method('transform')
            ->with($multimediaData, $opening)
            ->willReturn($expected);

        $result = $this->assembler->assembleOpening($multimediaData, $opening);

        static::assertSame($expected, $result);
    }

    #[Test]
    public function assembleOpeningShouldReturnNullWhenPipelineReturnsEmpty(): void
    {
        $opening = $this->createMock(Opening::class);
        $multimediaData = ['mm-1' => ['opening' => 'data']];

        $this->mediaTransformerPipeline->method('transform')
            ->willReturn([]);

        $result = $this->assembler->assembleOpening($multimediaData, $opening);

        static::assertNull($result);
    }

    #[Test]
    public function assembleEditorialShouldReturnNullForWidget(): void
    {
        $widget = $this->createMock(Widget::class);

        $result = $this->assembler->assembleEditorial([], $widget);

        static::assertNull($result);
    }

    #[Test]
    public function assembleEditorialShouldReturnNullWhenNoMultimediaId(): void
    {
        $opening = $this->createMock(PhotoExist::class);

        $this->multimediaImageService->method('getMultimediaId')
            ->willReturn(null);

        $result = $this->assembler->assembleEditorial([], $opening);

        static::assertNull($result);
    }

    #[Test]
    public function assembleEditorialShouldReturnNullWhenMultimediaNotFound(): void
    {
        $multimediaId = $this->createMock(MultimediaId::class);
        $multimediaId->method('id')->willReturn('missing-id');

        $opening = $this->createMock(PhotoExist::class);

        $this->multimediaImageService->method('getMultimediaId')
            ->willReturn($multimediaId);

        $result = $this->assembler->assembleEditorial([], $opening);

        static::assertNull($result);
    }

    #[Test]
    public function assembleEditorialShouldReturnCorrectStructure(): void
    {
        $expectedId = '123';
        $expectedCaption = 'Test caption';

        $multimediaId = $this->createMock(MultimediaId::class);
        $multimediaId->method('id')->willReturn($expectedId);

        $opening = $this->createMock(PhotoExist::class);

        $this->multimediaImageService->method('getMultimediaId')
            ->willReturn($multimediaId);

        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(0);
        $clipping->method('topLeftY')->willReturn(0);
        $clipping->method('bottomRightX')->willReturn(1920);
        $clipping->method('bottomRightY')->willReturn(1080);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')
            ->with(ClippingTypes::SIZE_MULTIMEDIA_BIG)
            ->willReturn($clipping);

        $multimedia = $this->createMock(Multimedia::class);
        $multimedia->method('id')->willReturn($expectedId);
        $multimedia->method('caption')->willReturn($expectedCaption);
        $multimedia->method('clippings')->willReturn($clippings);
        $multimedia->method('file')->willReturn('image.jpg');

        $this->thumbor->method('retriveCropBodyTagPicture')
            ->willReturn('https://example.com/image.jpg');

        $result = $this->assembler->assembleEditorial(
            [$expectedId => $multimedia],
            $opening
        );

        static::assertNotNull($result);
        static::assertSame($expectedId, $result['id']);
        static::assertSame('photo', $result['type']);
        static::assertSame($expectedCaption, $result['caption']);
        static::assertIsObject($result['shots']);
        static::assertSame('https://example.com/image.jpg', $result['photo']);
    }
}
