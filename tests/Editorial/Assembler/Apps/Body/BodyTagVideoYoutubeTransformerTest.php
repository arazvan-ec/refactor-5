<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagVideoYoutubeTransformer;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyTagVideoYoutube;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagVideoYoutubeTransformer::class)]
final class BodyTagVideoYoutubeTransformerTest extends TestCase
{
    private BodyTagVideoYoutubeTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new BodyTagVideoYoutubeTransformer('https://player.host');
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
    public function supportsShouldReturnBodyTagVideoYoutubeClass(): void
    {
        static::assertSame(BodyTagVideoYoutube::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArray(): void
    {
        $bodyElementMock = $this->createConfiguredMock(BodyTagVideoYoutube::class, [
            'type' => 'bodytagvideoyoutube',
            'id' => 'video123',
            'width' => 640,
            'height' => 360,
            'caption' => 'Sample Caption',
            'start' => 10,
        ]);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'bodytagvideoyoutube',
            'id' => 'video123',
            'width' => 640,
            'height' => 360,
            'caption' => 'Sample Caption',
            'start' => 10,
            'video' => 'https://player.host/embed/video/video123/640/360/10/',
        ], $result);
    }
}
