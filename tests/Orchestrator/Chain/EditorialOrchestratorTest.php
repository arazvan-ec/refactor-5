<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Chain;

use App\Editorial\GetEditorialHandler;
use App\Orchestrator\Chain\EditorialOrchestrator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(EditorialOrchestrator::class)]
final class EditorialOrchestratorTest extends TestCase
{
    private GetEditorialHandler $handler;
    private EditorialOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(GetEditorialHandler::class);
        $this->orchestrator = new EditorialOrchestrator($this->handler);
    }

    #[Test]
    public function executeDelegatesToHandler(): void
    {
        $editorialId = '12345';
        $expectedResult = ['id' => $editorialId, 'section' => [], 'body' => []];

        $request = $this->createMock(Request::class);
        $request->expects(static::once())
            ->method('get')
            ->with('id')
            ->willReturn($editorialId);

        $this->handler
            ->expects(static::once())
            ->method('handle')
            ->with($editorialId)
            ->willReturn($expectedResult);

        $result = $this->orchestrator->execute($request);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function canOrchestrateShouldReturnEditorial(): void
    {
        static::assertSame('editorial', $this->orchestrator->canOrchestrate());
    }
}
