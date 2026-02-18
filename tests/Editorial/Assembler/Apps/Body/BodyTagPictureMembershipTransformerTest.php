<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagPictureMembershipTransformer;
use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\PictureShots;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureId;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagPictureMembershipTransformer::class)]
final class BodyTagPictureMembershipTransformerTest extends TestCase
{
    private BodyTagPictureMembershipTransformer $transformer;
    /** @var PictureShots&MockObject */
    private PictureShots $pictureShots;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->pictureShots = $this->createMock(PictureShots::class);
        $this->transformer = new BodyTagPictureMembershipTransformer($this->pictureShots);
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
    public function supportsShouldReturnBodyTagPictureMembershipClass(): void
    {
        static::assertSame(BodyTagPictureMembership::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArrayWithShots(): void
    {
        $shots = [
            '1440w' => 'https://images.ecestaticos.dev/B26-5pH9vylfOiapiBjXanvO7Ho=/615x99:827x381/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '1200w' => 'https://images.ecestaticos.dev/gN2tLeVBCOcV5AKBmZeJhGYztTk=/615x99:827x381/1200x1600/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '996w' => 'https://images.ecestaticos.dev/YRLxy6ChIKjekgdg_BN1DirWtJ8=/615x99:827x381/996x1328/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '640w' => 'https://images.ecestaticos.dev/WByyZwZDIXdsAikGvHjMd3wOiUI=/615x99:827x381/560x747/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '390w' => 'https://images.ecestaticos.dev/6LRdLT09KxKdAIaRQV6gbHtiZSQ=/615x99:827x381/390x520/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '568w' => 'https://images.ecestaticos.dev/m70h5OCBdQyGjYRqai5qmRVZoUQ=/615x99:827x381/568x757/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '382w' => 'https://images.ecestaticos.dev/ws_0oo3JORfvWxI_XKyluvDeGRI=/615x99:827x381/382x509/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '328w' => 'https://images.ecestaticos.dev/YsYE5tLIS_WX3BU6agIfeikYUl8=/615x99:827x381/328x437/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
        ];
        $orientation = 'landscape';
        $url = 'https://images.ecestaticos.dev/B26-5pH9vylfOiapiBjXanvO7Ho=/615x99:827x381/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg';

        $this->pictureShots->method('retrieveShotsByPhotoId')->willReturn($shots);

        $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodytagPictureId->method('id')->willReturn('123');

        $bodyElement = $this->createMock(BodyTagPictureMembership::class);
        $bodyElement->method('id')->willReturn($bodytagPictureId);
        $bodyElement->method('orientation')->willReturn($orientation);

        $result = $this->transformer->transform($bodyElement, $this->context);

        static::assertSame($shots, $result['shots']);
        static::assertSame($orientation, $result['orientation']);
        static::assertSame($url, $result['url']);
    }

    #[Test]
    public function transformShouldReturnOnlyTypeWhenNoShots(): void
    {
        $this->pictureShots->method('retrieveShotsByPhotoId')->willReturn([]);

        $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodytagPictureId->method('id')->willReturn('123');

        $bodyElement = $this->createMock(BodyTagPictureMembership::class);
        $bodyElement->method('id')->willReturn($bodytagPictureId);
        $bodyElement->method('type')->willReturn('bodytagpicturemembership');

        $result = $this->transformer->transform($bodyElement, $this->context);

        static::assertSame(['type' => 'bodytagpicturemembership'], $result);
    }
}
