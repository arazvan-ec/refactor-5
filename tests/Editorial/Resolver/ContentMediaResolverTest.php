<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\BodyPhotosResolver;
use App\Editorial\Resolver\ContentMediaResolver;
use App\Editorial\Resolver\ContentMediaResult;
use App\Editorial\Resolver\MultimediaResolutionResult;
use App\Editorial\Resolver\MultimediaResolver;
use App\Editorial\Resolver\OpeningResolver;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentMediaResolver::class)]
final class ContentMediaResolverTest extends TestCase
{
    private MultimediaResolver&MockObject $multimediaResolver;
    private OpeningResolver&MockObject $openingResolver;
    private BodyPhotosResolver&MockObject $bodyPhotosResolver;
    private ContentMediaResolver $resolver;

    protected function setUp(): void
    {
        $this->multimediaResolver = $this->createMock(MultimediaResolver::class);
        $this->openingResolver = $this->createMock(OpeningResolver::class);
        $this->bodyPhotosResolver = $this->createMock(BodyPhotosResolver::class);

        $this->resolver = new ContentMediaResolver(
            $this->multimediaResolver,
            $this->openingResolver,
            $this->bodyPhotosResolver,
        );
    }

    #[Test]
    public function resolveShouldReturnContentMediaResult(): void
    {
        $bodyMock = $this->createMock(Body::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('body')->willReturn($bodyMock);

        $promise = $this->createMock(Promise::class);
        $multimediaResult = new MultimediaResolutionResult(promises: [$promise]);
        $this->multimediaResolver
            ->expects(static::once())
            ->method('startAsyncForEditorial')
            ->with($editorialMock)
            ->willReturn($multimediaResult);

        $openingData = ['opening-key' => ['opening' => 'data']];
        $this->openingResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($editorialMock)
            ->willReturn($openingData);

        $photoMock = $this->createMock(Photo::class);
        $this->bodyPhotosResolver
            ->expects(static::once())
            ->method('resolve')
            ->with($bodyMock)
            ->willReturn(['photo-1' => $photoMock]);

        $result = $this->resolver->resolve($editorialMock);

        static::assertInstanceOf(ContentMediaResult::class, $result);
        static::assertSame($multimediaResult, $result->multimediaResult);
        static::assertSame($openingData, $result->multimediaOpening);
        static::assertSame(['photo-1' => $photoMock], $result->bodyPhotos);
    }

    #[Test]
    public function settleAllMultimediaShouldMergeAndSettlePromises(): void
    {
        $promise1 = $this->createMock(Promise::class);
        $promise2 = $this->createMock(Promise::class);
        $promise3 = $this->createMock(Promise::class);

        $result1 = new MultimediaResolutionResult(promises: [$promise1]);
        $result2 = new MultimediaResolutionResult(promises: [$promise2, $promise3]);

        $multimediaMock = $this->createMock(AbstractMultimedia::class);
        $this->multimediaResolver
            ->expects(static::once())
            ->method('settle')
            ->with([$promise1, $promise2, $promise3])
            ->willReturn(['mm-1' => $multimediaMock]);

        $settled = $this->resolver->settleAllMultimedia($result1, $result2);

        static::assertSame(['mm-1' => $multimediaMock], $settled);
    }

    #[Test]
    public function settleAllMultimediaShouldHandleEmptyResults(): void
    {
        $this->multimediaResolver
            ->expects(static::once())
            ->method('settle')
            ->with([])
            ->willReturn([]);

        $settled = $this->resolver->settleAllMultimedia(
            new MultimediaResolutionResult(),
            new MultimediaResolutionResult(),
        );

        static::assertSame([], $settled);
    }

    #[Test]
    public function mergeOpeningsShouldCombineMainAndRelatedOpenings(): void
    {
        $mainOpening = ['main-key' => ['data' => 'main']];
        $related1 = new MultimediaResolutionResult(
            multimediaOpening: ['inserted-key' => ['data' => 'inserted']],
        );
        $related2 = new MultimediaResolutionResult(
            multimediaOpening: ['recommended-key' => ['data' => 'recommended']],
        );

        $merged = $this->resolver->mergeOpenings($mainOpening, $related1, $related2);

        static::assertCount(3, $merged);
        static::assertArrayHasKey('main-key', $merged);
        static::assertArrayHasKey('inserted-key', $merged);
        static::assertArrayHasKey('recommended-key', $merged);
    }

    #[Test]
    public function mergeOpeningsShouldReturnMainOnlyWhenNoRelated(): void
    {
        $mainOpening = ['main-key' => ['data' => 'main']];

        $merged = $this->resolver->mergeOpenings($mainOpening);

        static::assertSame($mainOpening, $merged);
    }
}
