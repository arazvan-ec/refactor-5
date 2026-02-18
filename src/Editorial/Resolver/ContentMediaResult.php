<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;

/**
 * @phpstan-type OpeningEntry array{opening: MultimediaPhoto, resource: Photo}
 */
final readonly class ContentMediaResult
{
    /**
     * @param array<string, OpeningEntry> $multimediaOpening
     * @param array<string, Photo>        $bodyPhotos
     */
    public function __construct(
        public MultimediaResolutionResult $multimediaResult,
        public array $multimediaOpening,
        public array $bodyPhotos,
    ) {
    }
}
