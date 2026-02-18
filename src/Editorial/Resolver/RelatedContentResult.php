<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

final readonly class RelatedContentResult
{
    public function __construct(
        public InsertedNewsResult $insertedNews,
        public RecommendedResult $recommended,
    ) {
    }

    public function combinedMultimediaResult(): MultimediaResolutionResult
    {
        return $this->insertedNews->multimediaResult
            ->merge($this->recommended->multimediaResult);
    }
}
