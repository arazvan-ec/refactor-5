<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\MultimediaResolutionResult;
use App\Editorial\Resolver\MultimediaResolver;
use App\Infrastructure\Service\MultimediaImageService;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultimediaResolver::class)]
final class MultimediaResolverTest extends TestCase
{
    private QueryMultimediaClient&MockObject $queryMultimediaClient;
    private MultimediaImageService&MockObject $multimediaImageService;
    private MultimediaResolver $resolver;

    protected function setUp(): void
    {
        $this->queryMultimediaClient = $this->createMock(QueryMultimediaClient::class);
        $this->multimediaImageService = $this->createMock(MultimediaImageService::class);
        $this->resolver = new MultimediaResolver(
            $this->queryMultimediaClient,
            $this->multimediaImageService,
        );
    }

    #[Test]
    public function startAsyncShouldCreatePromisesForEachId(): void
    {
        $promise1 = $this->createMock(Promise::class);
        $promise2 = $this->createMock(Promise::class);

        $this->queryMultimediaClient
            ->expects(static::exactly(2))
            ->method('findMultimediaById')
            ->willReturnOnConsecutiveCalls($promise1, $promise2);

        $result = $this->resolver->startAsync(['id1', 'id2']);

        static::assertCount(2, $result);
    }

    #[Test]
    public function settleShouldReturnEmptyArrayForEmptyPromises(): void
    {
        $result = $this->resolver->settle([]);

        static::assertSame([], $result);
    }

    #[Test]
    public function settleShouldFilterFulfilledPromises(): void
    {
        $multimedia1 = $this->createMock(AbstractMultimedia::class);
        $multimedia1->method('id')->willReturn('id1');

        $multimedia2 = $this->createMock(AbstractMultimedia::class);
        $multimedia2->method('id')->willReturn('id2');

        $promise1 = new FulfilledPromise($multimedia1);
        $promise2 = new RejectedPromise(new \Exception('failed'));
        $promise3 = new FulfilledPromise($multimedia2);

        $result = $this->resolver->settle([$promise1, $promise2, $promise3]);

        static::assertCount(2, $result);
        static::assertSame($multimedia1, $result['id1']);
        static::assertSame($multimedia2, $result['id2']);
    }

    #[Test]
    public function resolveForEditorialShouldReturnEmptyWhenNoMultimediaId(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);

        $this->multimediaImageService
            ->method('getMultimediaId')
            ->willReturn(null);

        $result = $this->resolver->resolveForEditorial($editorialMock);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveForEditorialShouldReturnEmptyForWidgets(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $widgetMock = $this->createMock(Widget::class);
        $editorialMock->method('multimedia')->willReturn($widgetMock);

        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('widget-123');

        $this->multimediaImageService
            ->method('getMultimediaId')
            ->willReturn($multimediaIdMock);

        $result = $this->resolver->resolveForEditorial($editorialMock);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveForEditorialShouldResolveMultimedia(): void
    {
        $multimediaIdValue = 'photo-123';
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn($multimediaIdValue);

        $photoExistMock = $this->createMock(PhotoExist::class);
        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('multimedia')->willReturn($photoExistMock);

        $this->multimediaImageService
            ->method('getMultimediaId')
            ->willReturn($multimediaIdMock);

        $resolvedMultimedia = $this->createMock(AbstractMultimedia::class);
        $resolvedMultimedia->method('id')->willReturn($multimediaIdValue);

        $promise = new FulfilledPromise($resolvedMultimedia);
        $this->queryMultimediaClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($multimediaIdValue, true)
            ->willReturn($promise);

        $result = $this->resolver->resolveForEditorial($editorialMock);

        static::assertCount(1, $result);
        static::assertSame($resolvedMultimedia, $result[$multimediaIdValue]);
    }

    #[Test]
    public function startAsyncForEditorialShouldReturnEmptyResultWhenNoMultimediaId(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);

        $this->multimediaImageService
            ->method('getMultimediaId')
            ->willReturn(null);

        $result = $this->resolver->startAsyncForEditorial($editorialMock);

        static::assertInstanceOf(MultimediaResolutionResult::class, $result);
        static::assertSame([], $result->promises);
        static::assertSame([], $result->multimediaOpening);
    }

    #[Test]
    public function startAsyncForEditorialShouldReturnEmptyResultForWidgets(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $widgetMock = $this->createMock(Widget::class);
        $editorialMock->method('multimedia')->willReturn($widgetMock);

        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('widget-123');

        $this->multimediaImageService
            ->method('getMultimediaId')
            ->willReturn($multimediaIdMock);

        $result = $this->resolver->startAsyncForEditorial($editorialMock);

        static::assertInstanceOf(MultimediaResolutionResult::class, $result);
        static::assertSame([], $result->promises);
    }

    #[Test]
    public function startAsyncForEditorialShouldReturnResultWithPromises(): void
    {
        $multimediaIdValue = 'photo-456';
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn($multimediaIdValue);

        $photoExistMock = $this->createMock(PhotoExist::class);
        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('multimedia')->willReturn($photoExistMock);

        $this->multimediaImageService
            ->method('getMultimediaId')
            ->willReturn($multimediaIdMock);

        $promiseMock = $this->createMock(Promise::class);
        $this->queryMultimediaClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($multimediaIdValue, true)
            ->willReturn($promiseMock);

        $result = $this->resolver->startAsyncForEditorial($editorialMock);

        static::assertInstanceOf(MultimediaResolutionResult::class, $result);
        static::assertCount(1, $result->promises);
    }
}
