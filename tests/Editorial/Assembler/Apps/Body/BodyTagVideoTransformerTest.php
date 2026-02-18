<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagVideoTransformer;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyTagVideo;
use Ec\Editorial\Domain\Model\Body\VideoId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagVideoTransformer::class)]
final class BodyTagVideoTransformerTest extends TestCase
{
    private BodyTagVideoTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new BodyTagVideoTransformer('https://player.host');
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
    public function supportsShouldReturnBodyTagVideoClass(): void
    {
        static::assertSame(BodyTagVideo::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArray(): void
    {
        $videoIdValue = 'video123';
        $videoIdMock = $this->createMock(VideoId::class);
        $videoIdMock->method('id')->willReturn($videoIdValue);

        $bodyElementMock = $this->createConfiguredMock(BodyTagVideo::class, [
            'type' => 'bodytagvideo',
            'id' => $videoIdMock,
            'width' => 640,
            'height' => 360,
            'caption' => 'Sample Caption',
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'bodytagvideo',
            'id' => $videoIdValue,
            'width' => 640,
            'height' => 360,
            'caption' => 'Sample Caption',
            'video' => 'https://player.host/embed/video/video123/640/360/',
        ], $result);
    }
}
