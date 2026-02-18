<?php

declare(strict_types=1);

namespace App\Editorial;

use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Editorial\Assembler\ResponseAssemblerInterface;
use App\Editorial\Resolver\ResolverPipeline;
use App\Exception\EditorialNotPublishedYetException;
use Ec\Editorial\Domain\Model\QueryEditorialClient;

final readonly class GetEditorialHandler
{
    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QueryLegacyClient $queryLegacyClient,
        private ResolverPipeline $resolverPipeline,
        private ResponseAssemblerInterface $assembler,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function handle(string $editorialId): array
    {
        $editorial = $this->queryEditorialClient->findEditorialById($editorialId);

        if (null === $editorial->sourceEditorial()) {
            return $this->queryLegacyClient->findEditorialById($editorialId);
        }

        if (!$editorial->isVisible()) {
            throw new EditorialNotPublishedYetException();
        }

        $aggregate = $this->resolverPipeline->resolve($editorial);

        return $this->assembler->assemble($aggregate);
    }
}
