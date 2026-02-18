<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Http\Promise\Promise;

/**
 * Immutable value object carrying unresolved multimedia promises
 * and opening data collected during editorial sub-resolution.
 *
 * @phpstan-type OpeningEntry array{opening: MultimediaPhoto, resource: Photo}
 */
final readonly class MultimediaResolutionResult
{
    /**
     * @param array<int, Promise>          $promises
     * @param array<string, OpeningEntry>  $multimediaOpening
     */
    public function __construct(
        public array $promises = [],
        public array $multimediaOpening = [],
    ) {
    }

    public function merge(self $other): self
    {
        return new self(
            promises: array_merge($this->promises, $other->promises),
            multimediaOpening: array_merge($this->multimediaOpening, $other->multimediaOpening),
        );
    }
}
