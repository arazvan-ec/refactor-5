<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\OpeningResolver;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Infrastructure\Client\Exceptions\InvalidBodyException;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(OpeningResolver::class)]
final class OpeningResolverTest extends TestCase
{
    private QueryMultimediaOpeningClient&MockObject $queryMultimediaOpeningClient;
    private MultimediaOrchestratorHandler&MockObject $multimediaOrchestratorHandler;
    private LoggerInterface&MockObject $logger;
    private OpeningResolver $resolver;

    protected function setUp(): void
    {
        $this->queryMultimediaOpeningClient = $this->createMock(QueryMultimediaOpeningClient::class);
        $this->multimediaOrchestratorHandler = $this->createMock(MultimediaOrchestratorHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resolver = new OpeningResolver(
            $this->queryMultimediaOpeningClient,
            $this->multimediaOrchestratorHandler,
            $this->logger,
        );
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenNoMultimediaId(): void
    {
        $openingMock = $this->createMock(Opening::class);
        $openingMock->method('multimediaId')->willReturn('');

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->method('opening')->willReturn($openingMock);

        $result = $this->resolver->resolve($editorialMock);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldReturnOpeningData(): void
    {
        $multimediaId = '123';
        $openingMock = $this->createMock(Opening::class);
        $openingMock->method('multimediaId')->willReturn($multimediaId);

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->method('opening')->willReturn($openingMock);

        $multimediaMock = $this->createMock(MultimediaPhoto::class);
        $photoMock = $this->createMock(Photo::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($multimediaId)
            ->willReturn($multimediaMock);

        $expectedResult = [
            $multimediaId => [
                'opening' => $multimediaMock,
                'resource' => $photoMock,
            ],
        ];

        $this->multimediaOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->with($multimediaMock)
            ->willReturn($expectedResult);

        $result = $this->resolver->resolve($editorialMock);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function resolveShouldLogWarningAndReturnEmptyOnOrchestratorTypeNotExist(): void
    {
        $multimediaId = '456';
        $openingMock = $this->createMock(Opening::class);
        $openingMock->method('multimediaId')->willReturn($multimediaId);

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->method('opening')->willReturn($openingMock);

        $multimediaMock = $this->createMock(AbstractMultimedia::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->willReturn($multimediaMock);

        $this->multimediaOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->willThrowException(new OrchestratorTypeNotExistException('Not found'));

        $this->logger
            ->expects(static::once())
            ->method('warning')
            ->with('Not found');

        $result = $this->resolver->resolve($editorialMock);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldLogWarningAndReturnEmptyOnInvalidBodyException(): void
    {
        $multimediaId = '789';
        $openingMock = $this->createMock(Opening::class);
        $openingMock->method('multimediaId')->willReturn($multimediaId);

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->method('opening')->willReturn($openingMock);

        $multimediaMock = $this->createMock(AbstractMultimedia::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->willReturn($multimediaMock);

        $this->multimediaOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->willThrowException(new InvalidBodyException('Invalid'));

        $this->logger
            ->expects(static::once())
            ->method('warning');

        $result = $this->resolver->resolve($editorialMock);

        static::assertSame([], $result);
    }
}
