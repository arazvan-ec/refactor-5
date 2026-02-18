<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;

/**
 * @phpstan-type GroupEntry array{editorial: Editorial, section: Section, signatures: array<int, array<string, mixed>>, multimediaId: string}
 */
final readonly class InsertedNewsResult
{
    /**
     * @param array<string, GroupEntry> $groups
     */
    public function __construct(
        public array $groups,
        public MultimediaResolutionResult $multimediaResult,
    ) {
    }
}
