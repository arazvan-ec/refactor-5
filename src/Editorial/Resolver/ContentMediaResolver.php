<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;

final readonly class ContentMediaResolver
{
    public function __construct(
        private MultimediaResolver $multimediaResolver,
        private OpeningResolver $openingResolver,
        private BodyPhotosResolver $bodyPhotosResolver,
    ) {
    }

    public function resolve(Editorial $editorial): ContentMediaResult
    {
        $multimediaResult = $this->multimediaResolver->startAsyncForEditorial($editorial);
        $multimediaOpening = $this->openingResolver->resolve($editorial);
        $bodyPhotos = $this->bodyPhotosResolver->resolve($editorial->body());

        return new ContentMediaResult(
            multimediaResult: $multimediaResult,
            multimediaOpening: $multimediaOpening,
            bodyPhotos: $bodyPhotos,
        );
    }

    /**
     * Settles all multimedia promises from all sources in a single batch.
     *
     * @return array<string, AbstractMultimedia>
     */
    public function settleAllMultimedia(MultimediaResolutionResult ...$results): array
    {
        $merged = new MultimediaResolutionResult();

        foreach ($results as $result) {
            $merged = $merged->merge($result);
        }

        return $this->multimediaResolver->settle($merged->promises);
    }

    /**
     * Merges multimedia opening data from the main editorial and related content.
     *
     * @param array<string, array<string, mixed>> $mainOpening
     *
     * @return array<string, array<string, mixed>>
     */
    public function mergeOpenings(array $mainOpening, MultimediaResolutionResult ...$results): array
    {
        $merged = $mainOpening;

        foreach ($results as $result) {
            $merged = array_merge($merged, $result->multimediaOpening);
        }

        return $merged;
    }
}
