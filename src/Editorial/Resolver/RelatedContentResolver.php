<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;

final readonly class RelatedContentResolver
{
    public function __construct(
        private InsertedNewsResolver $insertedNewsResolver,
        private RecommendedResolver $recommendedResolver,
    ) {
    }

    public function resolve(Editorial $editorial): RelatedContentResult
    {
        $insertedNews = $this->insertedNewsResolver->resolve($editorial);
        $recommended = $this->recommendedResolver->resolve($editorial);

        return new RelatedContentResult(
            insertedNews: $insertedNews,
            recommended: $recommended,
        );
    }
}
