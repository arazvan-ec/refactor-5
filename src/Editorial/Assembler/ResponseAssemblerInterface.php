<?php

declare(strict_types=1);

namespace App\Editorial\Assembler;

use App\Editorial\EditorialAggregate;

interface ResponseAssemblerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function assemble(EditorialAggregate $aggregate): array;
}
