<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\MultimediaResolver;
use App\Editorial\Resolver\RecommendedResolver;
use App\Editorial\Resolver\SignaturesResolver;
use App\Infrastructure\Service\MultimediaImageService;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\RecommendedEditorials;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(RecommendedResolver::class)]
final class RecommendedResolverTest extends TestCase
{
    private QueryEditorialClient&MockObject $queryEditorialClient;
    private QuerySectionClient&MockObject $querySectionClient;
    private QueryMultimediaOpeningClient&MockObject $queryMultimediaOpeningClient;
    private SignaturesResolver&MockObject $signaturesResolver;
    private MultimediaResolver&MockObject $multimediaResolver;
    private MultimediaImageService&MockObject $multimediaImageService;
    private LoggerInterface&MockObject $logger;
    private RecommendedResolver $resolver;

    protected function setUp(): void
    {
        $this->queryEditorialClient = $this->createMock(QueryEditorialClient::class);
        $this->querySectionClient = $this->createMock(QuerySectionClient::class);
        $this->queryMultimediaOpeningClient = $this->createMock(QueryMultimediaOpeningClient::class);
        $this->signaturesResolver = $this->createMock(SignaturesResolver::class);
        $this->multimediaResolver = $this->createMock(MultimediaResolver::class);
        $this->multimediaImageService = $this->createMock(MultimediaImageService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resolver = new RecommendedResolver(
            $this->queryEditorialClient,
            $this->querySectionClient,
            $this->queryMultimediaOpeningClient,
            $this->signaturesResolver,
            $this->multimediaResolver,
            $this->multimediaImageService,
            $this->logger,
        );
    }

    #[Test]
    public function resolveShouldReturnEmptyWhenNoRecommendedEditorials(): void
    {
        $recommendedEditorialsMock = $this->createMock(RecommendedEditorials::class);
        $recommendedEditorialsMock->method('editorialIds')->willReturn([]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('recommendedEditorials')->willReturn($recommendedEditorialsMock);

        $result = $this->resolver->resolve($editorialMock);

        static::assertSame([], $result['groups']);
        static::assertSame([], $result['promises']);
        static::assertSame([], $result['news']);
        static::assertSame([], $result['multimediaOpening']);
    }

    #[Test]
    public function resolveShouldReturnGroupsPromisesAndNewsForVisibleEditorials(): void
    {
        $recommendedId = 'recommended-123';
        $sectionId = 'section-456';
        $multimediaIdValue = 'multimedia-789';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($recommendedId);

        $recommendedEditorialsMock = $this->createMock(RecommendedEditorials::class);
        $recommendedEditorialsMock->method('editorialIds')->willReturn([$editorialIdMock]);

        $mainEditorialMock = $this->createMock(Editorial::class);
        $mainEditorialMock->method('recommendedEditorials')->willReturn($recommendedEditorialsMock);

        $multimediaIdDomainMock = $this->createMock(MultimediaId::class);
        $multimediaIdDomainMock->method('id')->willReturn($multimediaIdValue);

        $multimediaMock = $this->createMock(MultimediaEditorial::class);

        $signaturesMock = $this->createMock(Signatures::class);
        $signaturesMock->method('getArrayCopy')->willReturn([]);

        $recommendedEditorialMock = $this->createMock(NewsBase::class);
        $recommendedEditorialMock->method('isVisible')->willReturn(true);
        $recommendedEditorialMock->method('sectionId')->willReturn($sectionId);
        $recommendedEditorialMock->method('multimedia')->willReturn($multimediaMock);
        $recommendedEditorialMock->method('signatures')->willReturn($signaturesMock);

        $sectionMock = $this->createMock(Section::class);

        $this->queryEditorialClient
            ->expects(static::once())
            ->method('findEditorialById')
            ->with($recommendedId)
            ->willReturn($recommendedEditorialMock);

        $this->querySectionClient
            ->expects(static::once())
            ->method('findSectionById')
            ->with($sectionId)
            ->willReturn($sectionMock);

        $this->multimediaImageService
            ->method('getMultimediaId')
            ->willReturn($multimediaIdDomainMock);

        $promiseMock = $this->createMock(Promise::class);
        $this->multimediaResolver
            ->expects(static::once())
            ->method('startAsync')
            ->with([$multimediaIdValue])
            ->willReturn([$promiseMock]);

        $result = $this->resolver->resolve($mainEditorialMock);

        static::assertCount(1, $result['groups']);
        static::assertArrayHasKey($recommendedId, $result['groups']);
        static::assertSame($recommendedEditorialMock, $result['groups'][$recommendedId]['editorial']);
        static::assertSame($sectionMock, $result['groups'][$recommendedId]['section']);
        static::assertCount(1, $result['promises']);
        static::assertCount(1, $result['news']);
        static::assertSame($recommendedEditorialMock, $result['news'][0]);
    }

    #[Test]
    public function resolveShouldSkipInvisibleEditorials(): void
    {
        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn('invisible-123');

        $recommendedEditorialsMock = $this->createMock(RecommendedEditorials::class);
        $recommendedEditorialsMock->method('editorialIds')->willReturn([$editorialIdMock]);

        $mainEditorialMock = $this->createMock(Editorial::class);
        $mainEditorialMock->method('recommendedEditorials')->willReturn($recommendedEditorialsMock);

        $invisibleEditorialMock = $this->createMock(NewsBase::class);
        $invisibleEditorialMock->method('isVisible')->willReturn(false);

        $this->queryEditorialClient
            ->method('findEditorialById')
            ->willReturn($invisibleEditorialMock);

        $result = $this->resolver->resolve($mainEditorialMock);

        static::assertSame([], $result['groups']);
        static::assertSame([], $result['news']);
    }

    #[Test]
    public function resolveShouldLogErrorAndContinueOnException(): void
    {
        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn('error-123');

        $recommendedEditorialsMock = $this->createMock(RecommendedEditorials::class);
        $recommendedEditorialsMock->method('editorialIds')->willReturn([$editorialIdMock]);

        $mainEditorialMock = $this->createMock(Editorial::class);
        $mainEditorialMock->method('recommendedEditorials')->willReturn($recommendedEditorialsMock);

        $this->queryEditorialClient
            ->method('findEditorialById')
            ->willThrowException(new \Exception('Recommended not found'));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with('Recommended not found');

        $result = $this->resolver->resolve($mainEditorialMock);

        static::assertSame([], $result['groups']);
    }
}
