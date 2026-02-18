<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Service\MultimediaImageService;
use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\PhotoNotExist;
use Ec\Editorial\Domain\Model\Multimedia\Video;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Multimedia\Domain\Model\Clipping;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\Multimedia as MultimediaModel;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultimediaImageService::class)]
class MultimediaImageServiceTest extends TestCase
{
    private Thumbor&MockObject $thumbor;
    private MultimediaImageService $service;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->service = new MultimediaImageService($this->thumbor);
    }

    #[Test]
    public function getMultimediaIdShouldReturnIdWhenPhotoExist(): void
    {
        $multimediaId = $this->createMock(MultimediaId::class);
        $multimedia = $this->createMock(PhotoExist::class);
        $multimedia->method('id')->willReturn($multimediaId);

        $result = $this->service->getMultimediaId($multimedia);

        $this->assertSame($multimediaId, $result);
    }

    #[Test]
    public function getMultimediaIdShouldReturnIdWhenVideoWithPhotoExist(): void
    {
        $multimediaId = $this->createMock(MultimediaId::class);
        $photo = $this->createMock(PhotoExist::class);
        $photo->method('id')->willReturn($multimediaId);

        $video = $this->createMock(Video::class);
        $video->method('photo')->willReturn($photo);

        $result = $this->service->getMultimediaId($video);

        $this->assertSame($multimediaId, $result);
    }

    #[Test]
    public function getMultimediaIdShouldReturnIdWhenWidgetWithPhotoExist(): void
    {
        $multimediaId = $this->createMock(MultimediaId::class);
        $photo = $this->createMock(PhotoExist::class);
        $photo->method('id')->willReturn($multimediaId);

        $widget = $this->createMock(Widget::class);
        $widget->method('photo')->willReturn($photo);

        $result = $this->service->getMultimediaId($widget);

        $this->assertSame($multimediaId, $result);
    }

    #[Test]
    public function getMultimediaIdShouldReturnNullWhenVideoWithPhotoNotExist(): void
    {
        $photo = $this->createMock(PhotoNotExist::class);
        $video = $this->createMock(Video::class);
        $video->method('photo')->willReturn($photo);

        $result = $this->service->getMultimediaId($video);

        $this->assertNull($result);
    }

    #[Test]
    public function getMultimediaIdShouldReturnNullWhenUnsupportedType(): void
    {
        $multimedia = $this->createMock(Multimedia::class);

        $result = $this->service->getMultimediaId($multimedia);

        $this->assertNull($result);
    }

    #[Test]
    public function getShotsLandscapeShouldReturnShotsForAllSizes(): void
    {
        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(10);
        $clipping->method('topLeftY')->willReturn(20);
        $clipping->method('bottomRightX')->willReturn(100);
        $clipping->method('bottomRightY')->willReturn(200);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')
            ->with(ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clipping);

        $multimedia = $this->createMock(MultimediaModel::class);
        $multimedia->method('clippings')->willReturn($clippings);
        $multimedia->method('file')->willReturn('testfile.jpg');

        $callIndex = 0;
        $expectedUrls = [
            '202w' => 'https://thumbor/202w.jpg',
            '144w' => 'https://thumbor/144w.jpg',
            '128w' => 'https://thumbor/128w.jpg',
        ];

        $this->thumbor
            ->expects($this->exactly(3))
            ->method('retriveCropBodyTagPicture')
            ->willReturnCallback(function () use ($expectedUrls, &$callIndex) {
                $keys = array_keys($expectedUrls);

                return $expectedUrls[$keys[$callIndex++]];
            });

        $result = $this->service->getShotsLandscape($multimedia);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('202w', $result);
        $this->assertArrayHasKey('144w', $result);
        $this->assertArrayHasKey('128w', $result);
    }

    #[Test]
    public function getShotsLandscapeFromMediaShouldReturnShotsForAllSizes(): void
    {
        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(10);
        $clipping->method('topLeftY')->willReturn(20);
        $clipping->method('bottomRightX')->willReturn(100);
        $clipping->method('bottomRightY')->willReturn(200);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')
            ->with(ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clipping);

        $opening = $this->createMock(MultimediaPhoto::class);
        $opening->method('clippings')->willReturn($clippings);

        $resource = $this->createMock(Photo::class);
        $resource->method('file')->willReturn('testfile.jpg');

        $multimediaOpening = [
            'opening' => $opening,
            'resource' => $resource,
        ];

        $callIndex = 0;
        $expectedUrls = [
            '202w' => 'https://thumbor/202w.jpg',
            '144w' => 'https://thumbor/144w.jpg',
            '128w' => 'https://thumbor/128w.jpg',
        ];

        $this->thumbor
            ->expects($this->exactly(3))
            ->method('retriveCropBodyTagPicture')
            ->willReturnCallback(function () use ($expectedUrls, &$callIndex) {
                $keys = array_keys($expectedUrls);

                return $expectedUrls[$keys[$callIndex++]];
            });

        $result = $this->service->getShotsLandscapeFromMedia($multimediaOpening);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('202w', $result);
        $this->assertArrayHasKey('144w', $result);
        $this->assertArrayHasKey('128w', $result);
    }

    #[Test]
    public function sizesShouldContainExpectedEntries(): void
    {
        $this->assertArrayHasKey('202w', MultimediaImageService::SIZES);
        $this->assertArrayHasKey('144w', MultimediaImageService::SIZES);
        $this->assertArrayHasKey('128w', MultimediaImageService::SIZES);

        $this->assertSame('202', MultimediaImageService::SIZES['202w']['width']);
        $this->assertSame('152', MultimediaImageService::SIZES['202w']['height']);
        $this->assertSame('144', MultimediaImageService::SIZES['144w']['width']);
        $this->assertSame('108', MultimediaImageService::SIZES['144w']['height']);
        $this->assertSame('128', MultimediaImageService::SIZES['128w']['width']);
        $this->assertSame('96', MultimediaImageService::SIZES['128w']['height']);
    }
}
