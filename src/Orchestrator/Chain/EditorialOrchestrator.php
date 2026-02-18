<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain;

use App\Editorial\GetEditorialHandler;
use Symfony\Component\HttpFoundation\Request;

class EditorialOrchestrator implements EditorialOrchestratorInterface
{
    public function __construct(
        private readonly GetEditorialHandler $getEditorialHandler,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function execute(Request $request): array
    {
        /** @var string $id */
        $id = $request->get('id');

        return $this->getEditorialHandler->handle($id);
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }
}
