<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagPictureTransformer;
use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\PictureShots;
use App\Tests\Editorial\Assembler\Apps\Body\DataProvider\BodyTagPictureDataProvider;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagPictureTransformer::class)]
final class BodyTagPictureTransformerTest extends TestCase
{
    private BodyTagPictureTransformer $transformer;
    /** @var PictureShots&MockObject */
    private PictureShots $pictureShots;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->pictureShots = $this->createMock(PictureShots::class);
        $this->transformer = new BodyTagPictureTransformer($this->pictureShots);
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
    public function supportsShouldReturnBodyTagPictureClass(): void
    {
        static::assertSame(BodyTagPicture::class, $this->transformer->supports());
    }

    /**
     * @param array<string, string> $shots
     */
    #[DataProviderExternal(BodyTagPictureDataProvider::class, 'getData')]
    #[Test]
    public function transformShouldReturnExpectedArray(
        array $shots,
        string $caption,
        string $alternate,
        string $orientation,
        string $url,
        string $expectedCaption,
    ): void {
        $this->pictureShots->method('retrieveShotsByPhotoId')->willReturn($shots);

        $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodytagPictureId->method('id')->willReturn('123');

        $bodyElement = $this->createMock(BodyTagPicture::class);
        $bodyElement->method('id')->willReturn($bodytagPictureId);
        $bodyElement->method('caption')->willReturn($caption);
        $bodyElement->method('alternate')->willReturn($alternate);
        $bodyElement->method('orientation')->willReturn($orientation);

        $result = $this->transformer->transform($bodyElement, $this->context);

        static::assertSame($shots, $result['shots']);
        static::assertSame($expectedCaption, $result['caption']);
        static::assertSame($alternate, $result['alternate']);
        static::assertSame($orientation, $result['orientation']);
        static::assertSame($url, $result['url']);
    }

    #[Test]
    public function transformShouldReturnOnlyTypeWhenNoShots(): void
    {
        $this->pictureShots->method('retrieveShotsByPhotoId')->willReturn([]);

        $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodytagPictureId->method('id')->willReturn('123');

        $bodyElement = $this->createMock(BodyTagPicture::class);
        $bodyElement->method('id')->willReturn($bodytagPictureId);
        $bodyElement->method('type')->willReturn('bodytagpicture');

        $result = $this->transformer->transform($bodyElement, $this->context);

        static::assertSame(['type' => 'bodytagpicture'], $result);
    }
}
