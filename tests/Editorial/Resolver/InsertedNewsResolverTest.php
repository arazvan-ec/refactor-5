<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\InsertedNewsResolver;
use App\Editorial\Resolver\InsertedNewsResult;
use App\Editorial\Resolver\MultimediaResolver;
use App\Editorial\Resolver\SignaturesResolver;
use App\Infrastructure\Service\MultimediaImageService;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Editorial\Domain\Model\SignatureId;
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

#[CoversClass(InsertedNewsResolver::class)]
final class InsertedNewsResolverTest extends TestCase
{
    private QueryEditorialClient&MockObject $queryEditorialClient;
    private QuerySectionClient&MockObject $querySectionClient;
    private QueryMultimediaOpeningClient&MockObject $queryMultimediaOpeningClient;
    private SignaturesResolver&MockObject $signaturesResolver;
    private MultimediaResolver&MockObject $multimediaResolver;
    private MultimediaImageService&MockObject $multimediaImageService;
    private LoggerInterface&MockObject $logger;
    private InsertedNewsResolver $resolver;

    protected function setUp(): void
    {
        $this->queryEditorialClient = $this->createMock(QueryEditorialClient::class);
        $this->querySectionClient = $this->createMock(QuerySectionClient::class);
        $this->queryMultimediaOpeningClient = $this->createMock(QueryMultimediaOpeningClient::class);
        $this->signaturesResolver = $this->createMock(SignaturesResolver::class);
        $this->multimediaResolver = $this->createMock(MultimediaResolver::class);
        $this->multimediaImageService = $this->createMock(MultimediaImageService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resolver = new InsertedNewsResolver(
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
    public function resolveShouldReturnEmptyWhenNoInsertedNews(): void
    {
        $bodyMock = $this->createMock(Body::class);
        $bodyMock->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('body')->willReturn($bodyMock);

        $result = $this->resolver->resolve($editorialMock);

        static::assertInstanceOf(InsertedNewsResult::class, $result);
        static::assertSame([], $result->groups);
        static::assertSame([], $result->multimediaResult->promises);
        static::assertSame([], $result->multimediaResult->multimediaOpening);
    }

    #[Test]
    public function resolveShouldReturnGroupsAndPromisesForVisibleEditorials(): void
    {
        $insertedId = 'inserted-123';
        $sectionId = 'section-456';
        $multimediaIdValue = 'multimedia-789';

        // Setup body tag
        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($insertedId);

        $bodyTagMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyTagMock->method('editorialId')->willReturn($editorialIdMock);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock->method('bodyElementsOf')->willReturn([$bodyTagMock]);

        $mainEditorialMock = $this->createMock(Editorial::class);
        $mainEditorialMock->method('body')->willReturn($bodyMock);

        // Setup inserted editorial
        $multimediaIdDomainMock = $this->createMock(MultimediaId::class);
        $multimediaIdDomainMock->method('id')->willReturn($multimediaIdValue);

        $multimediaMock = $this->createMock(MultimediaEditorial::class);

        $signaturesMock = $this->createMock(Signatures::class);
        $signaturesMock->method('getArrayCopy')->willReturn([]);

        $insertedEditorialMock = $this->createMock(NewsBase::class);
        $insertedEditorialMock->method('isVisible')->willReturn(true);
        $insertedEditorialMock->method('sectionId')->willReturn($sectionId);
        $insertedEditorialMock->method('multimedia')->willReturn($multimediaMock);
        $insertedEditorialMock->method('signatures')->willReturn($signaturesMock);

        $sectionMock = $this->createMock(Section::class);

        $this->queryEditorialClient
            ->expects(static::once())
            ->method('findEditorialById')
            ->with($insertedId)
            ->willReturn($insertedEditorialMock);

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

        static::assertInstanceOf(InsertedNewsResult::class, $result);
        static::assertCount(1, $result->groups);
        static::assertArrayHasKey($insertedId, $result->groups);
        static::assertSame($insertedEditorialMock, $result->groups[$insertedId]['editorial']);
        static::assertSame($sectionMock, $result->groups[$insertedId]['section']);
        static::assertSame($multimediaIdValue, $result->groups[$insertedId]['multimediaId']);
        static::assertCount(1, $result->multimediaResult->promises);
    }

    #[Test]
    public function resolveShouldSkipInvisibleEditorials(): void
    {
        $insertedId = 'inserted-invisible';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($insertedId);

        $bodyTagMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyTagMock->method('editorialId')->willReturn($editorialIdMock);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock->method('bodyElementsOf')->willReturn([$bodyTagMock]);

        $mainEditorialMock = $this->createMock(Editorial::class);
        $mainEditorialMock->method('body')->willReturn($bodyMock);

        $insertedEditorialMock = $this->createMock(NewsBase::class);
        $insertedEditorialMock->method('isVisible')->willReturn(false);

        $this->queryEditorialClient
            ->method('findEditorialById')
            ->willReturn($insertedEditorialMock);

        $result = $this->resolver->resolve($mainEditorialMock);

        static::assertSame([], $result->groups);
    }

    #[Test]
    public function resolveShouldLogErrorAndContinueOnException(): void
    {
        $insertedId = 'inserted-error';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($insertedId);

        $bodyTagMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyTagMock->method('editorialId')->willReturn($editorialIdMock);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock->method('bodyElementsOf')->willReturn([$bodyTagMock]);

        $mainEditorialMock = $this->createMock(Editorial::class);
        $mainEditorialMock->method('body')->willReturn($bodyMock);

        $this->queryEditorialClient
            ->method('findEditorialById')
            ->willThrowException(new \Exception('Not found'));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with('Not found');

        $result = $this->resolver->resolve($mainEditorialMock);

        static::assertSame([], $result->groups);
    }
}
