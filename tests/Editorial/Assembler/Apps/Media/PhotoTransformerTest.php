<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Media;

use App\Editorial\Assembler\Apps\Media\PhotoTransformer;
use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\Clipping;
use Ec\Multimedia\Domain\Model\Multimedia\Clippings;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhotoTransformer::class)]
final class PhotoTransformerTest extends TestCase
{
    private PhotoTransformer $transformer;
    private Thumbor&MockObject $thumbor;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->transformer = new PhotoTransformer($this->thumbor);
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

        $multimedia = $this->createMock(MultimediaPhoto::class);

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMultimediaIdIsNull(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('abc123');

        $result = $this->transformer->transform([], $opening);

        self::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnPhotoDataForValidMultimedia(): void
    {
        $this->thumbor->method('retriveCropBodyTagPicture')->willReturn('thumbnail-url');

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('id1');

        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(0);
        $clipping->method('topLeftY')->willReturn(0);
        $clipping->method('bottomRightX')->willReturn(100);
        $clipping->method('bottomRightY')->willReturn(100);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')->willReturn($clipping);

        $multimedia = $this->createMock(MultimediaPhoto::class);
        $multimedia->method('clippings')->willReturn($clippings);
        $multimedia->method('caption')->willReturn('Test Caption');

        $photo = $this->createMock(Photo::class);
        $photo->method('file')->willReturn('file.jpg');

        $multimediaData = [
            'id1' => [
                'opening' => $multimedia,
                'resource' => $photo,
            ],
        ];

        $result = $this->transformer->transform($multimediaData, $opening);

        self::assertArrayHasKey('id', $result);
        self::assertSame('id1', $result['id']);
        self::assertSame('photo', $result['type']);
        self::assertSame('Test Caption', $result['caption']);
        self::assertSame('thumbnail-url', $result['photo']);

        self::assertArrayHasKey('shots', $result);
        self::assertInstanceOf(\stdClass::class, $result['shots']);

        $shots = (array) $result['shots'];

        self::assertArrayHasKey('4:3', $shots);
        self::assertArrayHasKey('16:9', $shots);
        self::assertArrayHasKey('3:4', $shots);
        self::assertArrayHasKey('3:2', $shots);
        self::assertArrayHasKey('2:3', $shots);

        self::assertCount(10, $shots['4:3']);
        self::assertCount(8, $shots['16:9']);
        self::assertCount(9, $shots['3:4']);
        self::assertCount(11, $shots['3:2']);
        self::assertCount(11, $shots['2:3']);

        foreach ($shots as $aspectRatio => $sizesArray) {
            foreach ($sizesArray as $sizeKey => $url) {
                self::assertSame(
                    'thumbnail-url',
                    $url,
                    "Shot for aspect ratio {$aspectRatio} and size {$sizeKey} should be 'thumbnail-url'"
                );
            }
        }
    }

    #[Test]
    public function shouldSupportMultimediaPhotoClass(): void
    {
        self::assertSame(MultimediaPhoto::class, $this->transformer->supports());
    }
}
