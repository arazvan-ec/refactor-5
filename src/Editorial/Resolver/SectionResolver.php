<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;

final readonly class SectionResolver
{
    public function __construct(
        private QuerySectionClient $querySectionClient,
    ) {
    }

    public function resolve(Editorial $editorial): Section
    {
        /** @var Section $section */
        $section = $this->querySectionClient->findSectionById($editorial->sectionId());

        return $section;
    }
}
