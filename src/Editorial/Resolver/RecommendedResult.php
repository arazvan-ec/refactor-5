<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;

/**
 * @phpstan-type GroupEntry array{editorial: Editorial, section: Section, signatures: array<int, array<string, mixed>>, multimediaId: string}
 */
final readonly class RecommendedResult
{
    /**
     * @param array<string, GroupEntry> $groups
     * @param array<Editorial>          $news
     */
    public function __construct(
        public array $groups,
        public array $news,
        public MultimediaResolutionResult $multimediaResult,
    ) {
    }
}
