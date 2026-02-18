<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;

interface EditorialResolverInterface
{
    public function resolve(Editorial $editorial, Section $section): void;
}
