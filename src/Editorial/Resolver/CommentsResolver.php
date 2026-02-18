<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;

final readonly class CommentsResolver
{
    public function __construct(
        private QueryLegacyClient $queryLegacyClient,
    ) {
    }

    public function resolve(string $editorialId): int
    {
        /** @var array{options: array{totalrecords?: int}} $comments */
        $comments = $this->queryLegacyClient->findCommentsByEditorialId($editorialId);

        return $comments['options']['totalrecords'] ?? 0;
    }
}
