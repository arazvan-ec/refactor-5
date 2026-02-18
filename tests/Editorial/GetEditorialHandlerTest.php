<?php

declare(strict_types=1);

namespace App\Tests\Editorial;

use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Editorial\Assembler\ResponseAssemblerInterface;
use App\Editorial\EditorialAggregate;
use App\Editorial\GetEditorialHandler;
use App\Editorial\Resolver\ResolverPipeline;
use App\Exception\EditorialNotPublishedYetException;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\SourceEditorial;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetEditorialHandler::class)]
final class GetEditorialHandlerTest extends TestCase
{
    private QueryEditorialClient $queryEditorialClient;
    private QueryLegacyClient $queryLegacyClient;
    private ResolverPipeline $resolverPipeline;
    private ResponseAssemblerInterface $assembler;
    private GetEditorialHandler $handler;

    protected function setUp(): void
    {
        $this->queryEditorialClient = $this->createMock(QueryEditorialClient::class);
        $this->queryLegacyClient = $this->createMock(QueryLegacyClient::class);
        $this->resolverPipeline = $this->createMock(ResolverPipeline::class);
        $this->assembler = $this->createMock(ResponseAssemblerInterface::class);

        $this->handler = new GetEditorialHandler(
            $this->queryEditorialClient,
            $this->queryLegacyClient,
            $this->resolverPipeline,
            $this->assembler,
        );
    }

    #[Test]
    public function handleReturnsLegacyResponseWhenSourceEditorialIsNull(): void
    {
        $editorialId = '12345';
        $legacyResponse = ['editorial' => ['id' => $editorialId]];

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('sourceEditorial')->willReturn(null);

        $this->queryEditorialClient
            ->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId)
            ->willReturn($editorial);

        $this->queryLegacyClient
            ->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId)
            ->willReturn($legacyResponse);

        $this->resolverPipeline->expects(static::never())->method('resolve');

        $result = $this->handler->handle($editorialId);

        static::assertSame($legacyResponse, $result);
    }

    #[Test]
    public function handleThrowsWhenEditorialIsNotVisible(): void
    {
        $editorialId = '12345';

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('sourceEditorial')->willReturn($this->createMock(SourceEditorial::class));
        $editorial->method('isVisible')->willReturn(false);

        $this->queryEditorialClient
            ->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId)
            ->willReturn($editorial);

        $this->expectException(EditorialNotPublishedYetException::class);

        $this->handler->handle($editorialId);
    }

    #[Test]
    public function handleResolvesAndAssemblesEditorial(): void
    {
        $editorialId = '12345';

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('sourceEditorial')->willReturn($this->createMock(SourceEditorial::class));
        $editorial->method('isVisible')->willReturn(true);

        $this->queryEditorialClient
            ->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId)
            ->willReturn($editorial);

        $aggregate = $this->createMock(EditorialAggregate::class);

        $this->resolverPipeline
            ->expects(static::once())
            ->method('resolve')
            ->with($editorial)
            ->willReturn($aggregate);

        $expectedResponse = ['id' => $editorialId, 'section' => [], 'body' => []];

        $this->assembler
            ->expects(static::once())
            ->method('assemble')
            ->with($aggregate)
            ->willReturn($expectedResponse);

        $result = $this->handler->handle($editorialId);

        static::assertSame($expectedResponse, $result);
    }
}
