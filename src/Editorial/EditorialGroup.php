<?php

declare(strict_types=1);

namespace App\Editorial;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;

/**
 * Lightweight DTO for inserted/recommended editorials.
 */
final readonly class EditorialGroup
{
    /**
     * @param array<int, array<string, mixed>> $signatures
     */
    public function __construct(
        public Editorial $editorial,
        public Section $section,
        public array $signatures,
        public string $multimediaId,
    ) {
    }
}
